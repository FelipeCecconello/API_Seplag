Autor: Felipe Cecconello Fontana

📋 Descrição do Projeto
Teste prático para processo seletivo da SEPLAG


🚀 Como Iniciar o Projeto
Pré-requisitos
Docker e Docker Compose instalados

Acesso administrativo para editar o arquivo hosts

Passos para Inicialização
Adicione o MinIO ao seu arquivo hosts:

Windows: C:\Windows\System32\drivers\etc\hosts

Linux/Mac: /etc/hosts

Adicione a linha:

```bash
127.0.0.1 minio
```

Inicie os containers:

```bash
docker-compose up -d
```

Execute as migrations e seeds:

```bash
docker-compose exec app php artisan migrate
```

Acesse a aplicação:

API: http://localhost:8000

MinIO Console: http://localhost:8900 (usuário: sail, senha: password)

⚙️ Configuração do Ambiente
Configurações de env importantes do projeto:

```ini
APP_NAME=SEPLAG
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=seplag
DB_USERNAME=laravel
DB_PASSWORD=secret

AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pessoa-fotos
AWS_ENDPOINT=http://minio:9000

API_ALLOWED_DOMAINS=http://localhost,http://localhost:8000
```


🔑 Acesso Inicial
Um usuário admin padrão é criado automaticamente:

Email: admin@admin.com

Senha: 123456


📡 Endpoints da API
Todos os endpoints GET suportam paginação.
Execute o comando abaixo para listar todos os endpoints da API

```bash
docker-compose exec app php artisan route:list
```

Collection Postman
Uma collection completa para teste da API está disponível em:
docs/SEPLAG_API.postman_collection.json

Importe no Postman para ter acesso a todos os endpoints pré-configurados.


🔄 Fluxo de Autenticação
Faça login em /api/auth/login

Use o token recebido no header Authorization: Bearer {token} ou adicione uma variavel para utilizar em todas as requisições no Postman

Tokens expiram em 5 minutos - renove com /api/auth/renew

ℹ️ Informações Adicionais

Armazenamento: Fotos são salvas no MinIO (S3-compatible)
Dominio: A env API_ALLOWED_DOMAINS controla quais dominios poderão acessar a api, o localhost esta configurado para poder acessar por padrão.
Bando de dados: Todos os campos das tabelas por padrão estão como não nulos, exceto: lotacao.lot_data_remocao e sertidor_temporario.st_data_demissao
Atualizar fotos: Para atualizar as fotos dos servidores foi criada uma rota POST expecifica para isso pois o PHP não suporta PUT com arquivos