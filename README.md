# API Laravel - Produtos e Categorias

Este é um projeto de API desenvolvido em Laravel (versão mais recente) que gerencia produtos e categorias, incluindo autenticação de usuário via JWT.

## Funcionalidades

*   Autenticação de Usuários (Registro, Login, Logout, Refresh Token, Perfil) via JWT.
*   CRUD completo para Produtos (Listar, Criar, Ver por ID, Ver por Nome, Atualizar, Deletar).
*   Estrutura em camadas: Controller, Service, Repository.
*   Validação de dados de entrada (DTOs / Form Requests).
*   Tratamento padronizado de erros e respostas JSON.
*   Logs detalhados das operações.
*   Testes unitários para Serviços e Controladores.
*   Documentação da API interativa via Swagger.

## Requisitos

*   PHP (versão compatível com o Laravel instalado, ex: >= 8.1)
*   Composer
*   Banco de Dados (MySQL, PostgreSQL, etc.)
*   Um ambiente de desenvolvimento como Laragon, XAMPP, WAMP, ou Docker.

## Configuração com Laragon

1.  **Clonar/Extrair o Projeto:** Coloque a pasta do projeto (`laravel_api_project`) dentro do diretório `www` do seu Laragon (ex: `C:\laragon\www\laravel_api_project`).
2.  **Iniciar Laragon:** Certifique-se de que o Apache (ou Nginx) e o MySQL estejam rodando no Laragon.
3.  **Banco de Dados:**
    *   Acesse o phpMyAdmin ou outra ferramenta de banco de dados via Laragon.
    *   Crie um novo banco de dados (ex: `laravel_api_db`).
4.  **Configurar Variáveis de Ambiente:**
    *   Renomeie o arquivo `.env.example` para `.env` (se não existir).
    *   Abra o arquivo `.env` e atualize as configurações do banco de dados:
        ```dotenv
        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=laravel_api_db  # Nome do banco que você criou
        DB_USERNAME=root            # Usuário padrão do Laragon (geralmente root)
        DB_PASSWORD=               # Senha padrão do Laragon (geralmente vazia)
        ```
    *   Verifique se a `APP_URL` está correta (Laragon geralmente cria uma URL como `http://laravel_api_project.test`). Você pode definir `APP_URL=http://localhost:8000` se for usar `php artisan serve`.
    *   Certifique-se que `JWT_SECRET` está presente (deve estar, pois foi gerado durante a instalação do JWT).
5.  **Instalar Dependências:**
    *   Abra o Terminal do Laragon (ou outro terminal na pasta do projeto).
    *   Execute: `composer install`
6.  **Gerar Chave da Aplicação:**
    *   Execute: `php artisan key:generate`
7.  **Executar Migrations:**
    *   Execute: `php artisan migrate` (Isso criará as tabelas `users`, `categories`, `products`, etc.)
8.  **(Opcional) Popular Banco (Seeders):** Se houver seeders, execute: `php artisan db:seed`
9.  **Iniciar o Servidor:**
    *   **Via Laragon:** A URL bonita (ex: `http://laravel_api_project.test`) deve funcionar se o Laragon estiver configurado corretamente.
    *   **Via Artisan:** Execute `php artisan serve`. A API estará disponível em `http://localhost:8000` (ou a porta indicada).

## Uso da API

1.  **Documentação Swagger:** Acesse `/api/documentation` na URL base da sua aplicação (ex: `http://localhost:8000/api/documentation` ou `http://laravel_api_project.test/api/documentation`). A documentação interativa permite testar todos os endpoints.
2.  **Endpoints Principais:**
    *   `/api/auth/register` (POST): Registrar novo usuário.
    *   `/api/auth/login` (POST): Obter token JWT.
    *   `/api/auth/me` (GET): Ver perfil do usuário autenticado (requer token).
    *   `/api/products` (GET): Listar produtos (requer token).
    *   `/api/products` (POST): Criar produto (requer token).
    *   `/api/products/{id}` (GET): Ver produto por ID (requer token).
    *   `/api/products/search?name={nome}` (GET): Buscar produto por nome (requer token).
    *   `/api/products/{id}` (PUT/PATCH): Atualizar produto (requer token).
    *   `/api/products/{id}` (DELETE): Deletar produto (requer token).
3.  **Autenticação:** Para acessar endpoints protegidos, inclua o token JWT no cabeçalho `Authorization`:
    `Authorization: Bearer <seu_token_jwt>`

## Executando Testes

*   Para rodar os testes unitários e de features, execute: `php artisan test`


