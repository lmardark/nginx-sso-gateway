# expert-bassoon

Sistema de autenticação centralizada (SSO) construído com **Laravel 13**, **Inertia.js** e **Vue 3**. Permite que múltiplas aplicações compartilhem uma única sessão autenticada via cookie de domínio compartilhado, com validação delegada ao Nginx através do módulo `auth_request`.

---

## Sumário

- [Visão geral](#visão-geral)
- [Stack](#stack)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Rotas](#rotas)
- [Fluxo de autenticação](#fluxo-de-autenticação)
- [SSO JWT — integração cross-domain](#sso-jwt--integração-cross-domain)
- [Painel administrativo](#painel-administrativo)
- [Primeiros passos — Setup inicial](#primeiros-passos--setup-inicial)
- [Comandos disponíveis](#comandos-disponíveis)
- [Testes](#testes)
- [Análise estática](#análise-estática)
- [Qualidade de código](#qualidade-de-código)
- [Deploy e Nginx](#deploy-e-nginx)
- [Docker Compose](#docker-compose)
- [Licença](#licença)

---

## Visão geral

O **expert-bassoon** atua como o ponto central de autenticação para um conjunto de aplicações em subdomínios do mesmo domínio. O fluxo funciona assim:

1. O usuário acessa qualquer aplicação protegida.
2. O Nginx dessa aplicação faz uma subrequisição interna ao endpoint `/auth/check` deste autenticador, repassando o cookie de sessão.
3. O autenticador responde `200` (sessão válida) ou `401` (não autenticado).
4. Em caso de `401`, o Nginx redireciona o usuário ao login com um parâmetro `return_to`, que devolve o usuário à aplicação original após autenticar.

```
Browser → app1.dominio.com.br
            │
            ├─ Nginx auth_request → auth.dominio.com.br/auth/check
            │       ├─ 200: serve a requisição normalmente
            │       └─ 401: redireciona para auth.dominio.com.br/login?return_to=...
            │
            └─ Usuário faz login → redirecionado de volta para app1.dominio.com.br
```

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Vue 3, Inertia.js, TypeScript |
| Estilização | Tailwind CSS 4 |
| Build | Vite 8 |
| Banco de dados | SQLite (padrão) |
| Testes | Pest 4 |
| Análise estática | PHPStan nível 6, Larastan |
| Linting PHP | Laravel Pint |
| Linting JS/TS | ESLint + Prettier |
| Qualidade | SonarQube |

---

## Requisitos

- PHP **8.3** ou superior com extensões: `pdo_sqlite`, `mbstring`, `xml`, `curl`, `zip`
- Composer **2.x**
- Node.js **20+** e pnpm **9+**
- Nginx **1.5.4+** (com `ngx_http_auth_request_module`)

---

## Instalação

### Instalação rápida (recomendado)

O projeto inclui um script `setup` que executa todos os passos automaticamente:

```bash
git clone <repo-url> expert-bassoon
cd expert-bassoon
composer run setup
```

O script executa em sequência:
1. `composer install`
2. Instala o `larastan`
3. Copia `.env.example` → `.env` (se não existir)
4. Gera a `APP_KEY`
5. Roda as migrations
6. `pnpm install`
7. `pnpm run build`

### Instalação manual

```bash
git clone <repo-url> expert-bassoon
cd expert-bassoon

# Dependências PHP
composer install

# Ambiente
cp .env.example .env
php artisan key:generate

# Banco de dados
touch database/database.sqlite
php artisan migrate

# Dependências Node e build
pnpm install
pnpm run build
```

### Servidor de desenvolvimento

```bash
composer run dev
```

Esse comando sobe em paralelo:
- `php artisan serve` — servidor PHP na porta `8000`
- `php artisan queue:listen` — fila de jobs
- `php artisan pail` — log em tempo real
- `pnpm run dev` — Vite com HMR

---

## Variáveis de ambiente

Copie `.env.example` e ajuste os valores:

```dotenv
APP_NAME="expert-bassoon"
APP_ENV=local           # production em produção
APP_DEBUG=true          # false em produção
APP_URL=http://localhost

# Banco de dados (SQLite por padrão)
DB_CONNECTION=sqlite

# Sessão — configuração crítica para SSO
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=null                  # produção: .seudominio.com.br
SESSION_SECURE_COOKIE=false          # produção: true
SESSION_SAME_SITE=lax

# Segurança no redirecionamento pós-login
# Aceita apenas URLs cujo host termine com este valor
ALLOWED_HOST_REDIRECT=               # ex: seudominio.com.br
```

### Variáveis para SSO em produção

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `SESSION_DOMAIN` | `.seudominio.com.br` | Ponto na frente compartilha o cookie entre todos os subdomínios |
| `SESSION_SECURE_COOKIE` | `true` | Obrigatório com HTTPS — impede que o cookie seja enviado em HTTP |
| `SESSION_SAME_SITE` | `lax` | Permite envio do cookie em navegações cross-site (necessário para o `return_to`) |
| `ALLOWED_HOST_REDIRECT` | `seudominio.com.br` | Valida o host do `return_to` para evitar open redirect |

---

## Estrutura do projeto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── SetupController.php     # Configuração inicial (primeiro acesso)
│   │   │   └── UserController.php      # CRUD de usuários
│   │   └── Auth/
│   │       └── AuthController.php      # Login e logout
│   ├── Middleware/
│   │   ├── CheckFirstSetup.php         # Redireciona para /setup se não houver usuários
│   │   ├── HandleInertiaRequests.php   # Compartilha dados globais com o frontend
│   │   └── UniversalAuth.php          # Controle de acesso às rotas
│   └── Requests/
│       └── Auth/
│           └── LoginRequest.php        # Validação do formulário de login
├── Models/
│   └── User.php
└── Services/
    └── Auth/
        └── AuthService.php             # Lógica de autenticação

resources/js/
├── pages/
│   ├── Auth/
│   │   └── Login.vue
│   ├── Admin/
│   │   ├── Setup.vue                   # Página de configuração inicial
│   │   └── Users/
│   │       ├── Index.vue               # Listagem de usuários
│   │       ├── Create.vue              # Criação de usuário
│   │       └── Edit.vue                # Edição de usuário
│   └── Dashboard.vue
└── routes/
    └── index.ts                        # Rotas geradas pelo Wayfinder
```

---

## Rotas

| Método | URI | Descrição | Auth |
|--------|-----|-----------|------|
| `GET` | `/` | Redireciona para `/login` | — |
| `GET` | `/login` | Página de login | — |
| `POST` | `/login` | Processa o login | — |
| `POST` | `/logout` | Encerra a sessão | — |
| `GET` | `/setup` | Configuração inicial do administrador | — |
| `POST` | `/setup` | Cria o primeiro usuário administrador | — |
| `GET` | `/dashboard` | Dashboard principal | Sim |
| `GET` | `/health` | Health check do serviço (retorna JSON) | — |
| `GET` | `/auth/check` | Validação de sessão para o Nginx `auth_request` | — |
| `GET` | `/sso/token` | Emite JWT de uso único para SSO cross-domain | Sim |
| `POST` | `/sso/validate` | Valida o JWT emitido (chamado pelo backend da app) | — |
| `GET` | `/admin/audit` | Logs de auditoria | Admin |
| `GET/PUT` | `/admin/settings` | Personalização da página de login | Admin |
| `GET` | `/admin/users` | Lista todos os usuários | Admin |
| `GET` | `/admin/users/create` | Formulário de criação | Admin |
| `POST` | `/admin/users` | Salva novo usuário | Admin |
| `GET` | `/admin/users/{id}/edit` | Formulário de edição | Admin |
| `PUT` | `/admin/users/{id}` | Atualiza usuário | Admin |
| `DELETE` | `/admin/users/{id}` | Remove usuário | Admin |
| `GET` | `/admin/apps` | Lista aplicações integradas ao SSO | Admin |
| `GET` | `/admin/apps/create` | Formulário de nova aplicação | Admin |
| `POST` | `/admin/apps` | Registra nova aplicação | Admin |
| `GET` | `/admin/apps/{id}/edit` | Formulário de edição da aplicação | Admin |
| `PUT` | `/admin/apps/{id}` | Atualiza aplicação | Admin |
| `DELETE` | `/admin/apps/{id}` | Remove aplicação | Admin |
| `POST` | `/admin/apps/{id}/regenerate-key` | Regenera a API Key da aplicação | Admin |


---

## Fluxo de autenticação

### Login

```
POST /login
  └── LoginRequest (validação)
        └── AuthService::autenticationFunction()
              └── Auth::attempt(['username', 'password', 'remember'])
                    ├── Sucesso → redireciona para return_to ou /dashboard
                    └── Falha   → volta com erro no campo "username"
```

O redirecionamento pós-login aceita um parâmetro `return_to` na query string ou na sessão (`url.intended`). O host do `return_to` é validado contra `ALLOWED_HOST_REDIRECT` para prevenir open redirect.

### Middleware de acesso

**`CheckFirstSetup`** — roda em toda requisição web (antes da autenticação):
- Se não há usuários no banco → redireciona para `/setup`
- Se há usuários e a rota é `/setup` → redireciona para `/login`

**`UniversalAuth`** — roda nas rotas protegidas:
- Verifica `Auth::check()` ou cookie `remember_me`
- Não autenticado tentando rota protegida → redireciona para `/login?return_to=...`
- Autenticado tentando acessar `/login` → redireciona para `/dashboard`

---

## SSO JWT — integração cross-domain

Além do SSO via cookie (subdomínios), o gateway oferece um fluxo JWT para integrar aplicações em **domínios completamente diferentes**.

### Como funciona

```
1. Usuário está autenticado em auth.seudominio.com
2. Aplicação em outro-site.com redireciona o usuário para:
   GET https://auth.seudominio.com/sso/token
       ?app=<API_KEY_DA_APLICAÇÃO>
       &redirect=https://outro-site.com/sso/callback

3. O gateway valida a sessão, gera um JWT HS256 de uso único (TTL: 2 min)
   e redireciona para:
   https://outro-site.com/sso/callback?sso_token=<jwt>

4. O backend de outro-site.com valida o token:
   POST https://auth.seudominio.com/sso/validate
   { "token": "<jwt>", "api_key": "<API_KEY_DA_APLICAÇÃO>" }

5. Resposta de sucesso:
   { "valid": true, "user": { "id": 1, "username": "joao", "nickname": "João", "is_admin": false } }
```

### Cadastrar uma aplicação

1. Acesse `/admin/apps` → **Nova aplicação**
2. Informe o nome e os **domínios permitidos** (um por linha)
3. Opcionalmente, informe a URL de callback padrão
4. A **API Key** é gerada automaticamente — guarde-a com segurança
5. Para rotacionar a chave, clique em **Regenerar** na tela de edição

### Payload do JWT

```json
{
  "sub": "joao",
  "user_id": 1,
  "nickname": "João Silva",
  "is_admin": false,
  "app": "Minha Aplicação",
  "jti": "<id único>",
  "iat": 1746500000,
  "exp": 1746500120
}
```

O JWT é assinado com **HS256** usando a API Key da aplicação. O `jti` é registrado no banco e invalidado após o primeiro uso — **tokens não podem ser reutilizados**.

---

## Painel administrativo

O painel administrativo (`/home` → seção "Ações do Administrador") centraliza todas as funções de gerenciamento.

### Módulos disponíveis

| Módulo | URL | Descrição |
|--------|-----|-----------|
| Gerenciar Usuários | `/admin/users` | CRUD completo de usuários |
| Gerenciar Aplicações | `/admin/apps` | Registro de apps SSO e gerenciamento de API Keys |
| Auditoria | `/admin/audit` | Histórico paginado de eventos do sistema |
| Personalizar Login | `/admin/settings` | Logo, cores e CSS da página de login |

### Usuários

- **Listagem** — tabela com busca em tempo real por nome de usuário
- **Criar usuário** — formulário com usuário, senha e confirmação
- **Editar usuário** — atualiza nome de usuário e/ou senha (senha opcional na edição)
- **Excluir usuário** — modal de confirmação antes da exclusão
- **Indicador "você"** — destaca o próprio usuário na listagem

### Aplicações SSO

- **Cadastro** — nome, domínios permitidos (um por linha), URL de callback e status
- **API Key** — gerada automaticamente; visível na tela de edição (pode ser regenerada)
- **Subdomínios** — se `app.seudominio.com` está na lista, todos os subdomínios são aceitos

### Primeiro acesso

Se o banco estiver vazio (ex: novo ambiente), qualquer acesso ao sistema redireciona automaticamente para `/setup`. Nessa página é possível definir o usuário e senha do administrador. Após o cadastro, o login é feito automaticamente e o usuário é redirecionado ao dashboard.

---

## Primeiros passos — Setup inicial

```bash
# 1. Clone e instale
git clone <repo-url> expert-bassoon && cd expert-bassoon
composer run setup

# 2. Inicie o servidor de desenvolvimento
composer run dev

# 3. Acesse http://localhost:8000
#    → Será redirecionado automaticamente para /setup
#    → Defina usuário e senha do administrador
#    → Login automático → Dashboard
```

---

## Comandos disponíveis

### Composer

| Comando | Descrição |
|---------|-----------|
| `composer run setup` | Instalação completa do zero |
| `composer run dev` | Servidor de desenvolvimento completo (PHP + Vite + queue + logs) |
| `composer run lint` | Corrige o estilo do código PHP com Pint |
| `composer run lint:check` | Verifica o estilo sem modificar arquivos |
| `composer run analyse` | Análise estática com PHPStan nível 6 |
| `composer run test` | Limpa cache, verifica lint e roda os testes |
| `composer run ci:check` | Verificação completa de CI (lint, format, types, testes) |

### pnpm

| Comando | Descrição |
|---------|-----------|
| `pnpm run dev` | Inicia o Vite com HMR |
| `pnpm run build` | Build de produção |
| `pnpm run lint` | Corrige problemas de ESLint |
| `pnpm run lint:check` | Verifica ESLint sem modificar |
| `pnpm run format` | Formata arquivos com Prettier |
| `pnpm run format:check` | Verifica formatação sem modificar |
| `pnpm run types:check` | Verifica tipos TypeScript com vue-tsc |

---

## Testes

O projeto usa **Pest 4** com o plugin Laravel.

```bash
# Rodar todos os testes
php artisan test

# Ou via composer (inclui lint)
composer run test

# Com cobertura
php artisan test --coverage
```

A suíte cobre:

- `AuthControllerTest` — login com credenciais válidas/inválidas, redirecionamentos
- `LoginRequestTest` — validação dos campos do formulário
- `UniversalAuthTest` — comportamento do middleware de acesso
- `AuthServiceTest` — lógica de autenticação

---

## Análise estática

```bash
composer run analyse
# equivale a: ./vendor/bin/phpstan --level=6 analyse app/*
```

O projeto utiliza **PHPStan nível 6** com **Larastan** para cobertura completa das classes do Laravel. Nenhum erro deve ser reportado.

---

## Qualidade de código

### PHP — Laravel Pint

Configurado com o preset `laravel` e regras adicionais em `pint.json`. Principais convenções:

- `declare(strict_types=1)` obrigatório em todos os arquivos
- Classes finais por padrão (`final_class`)
- Imports totalmente qualificados (`fully_qualified_strict_types`)
- Sem `else` desnecessário (`no_useless_else`)
- Funções de string multibyte (`mb_str_functions`)

### TypeScript / Vue — ESLint + Prettier

Configurado em `eslint.config.js` com os plugins `eslint-plugin-vue`, `typescript-eslint` e `@stylistic/eslint-plugin`. Formatação gerenciada pelo Prettier com `prettier-plugin-tailwindcss`.

---

## Deploy e Nginx

O diretório `nginx/` contém exemplos prontos de configuração:

| Arquivo | Uso |
|---------|-----|
| `nginx/sso-gateway.conf` | Servidor do gateway SSO (`auth.seudominio.com`) |
| `nginx/app-subdomain.conf` | App no mesmo domínio — usa cookie + `auth_request` |
| `nginx/app-crossdomain.conf` | App em domínio diferente — usa fluxo JWT |

### Configuração rápida para subdomínio

```nginx
# Em /etc/nginx/sites-enabled/minha-app.conf
server {
    listen 443 ssl;
    server_name app.seudominio.com;

    location = /auth/check {
        internal;
        proxy_pass              https://auth.seudominio.com/auth/check;
        proxy_pass_request_body off;
        proxy_set_header        Content-Length "";
        proxy_set_header        Cookie $http_cookie;
    }

    location / {
        auth_request /auth/check;
        error_page 401 = @login_redirect;
        proxy_pass http://localhost:3000;
    }

    location @login_redirect {
        return 302 https://auth.seudominio.com/login?return_to=$scheme://$host$request_uri;
    }
}
```

### Deploy da aplicação

```bash
# Build dos assets
pnpm run build

# Cache de configurações
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link do storage público (logos)
php artisan storage:link

# Permissões
chown -R www-data:www-data /var/www/auth
chmod -R 775 /var/www/auth/storage /var/www/auth/bootstrap/cache
```

---

## Docker Compose

Um exemplo de configuração Docker está disponível em `docker-compose.example.yml`:

```bash
cp docker-compose.example.yml docker-compose.yml
# Edite as variáveis de ambiente no arquivo
docker compose up -d
```

Inclui os serviços `app` (PHP-FPM) e `nginx` com volume compartilhado para `storage/`.

---

## Licença

MIT License — © 2026 Lucas Matheus. Veja o arquivo [LICENSE](./LICENSE) para mais detalhes.
