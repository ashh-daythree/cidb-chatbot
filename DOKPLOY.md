# Dokploy Deployment

This repository uses one Docker container with two supervised processes:

- PHP serves the frontend and backend on port `8080`.
- Python/Uvicorn serves OCR internally on `127.0.0.1:8002`.

## Dokploy settings

Create an application from this Git repository and select **Dockerfile** deployment.

- Dockerfile path: `Dockerfile`
- Container port: `8080`
- Build context: repository root

Set the production environment variables in Dokploy. Do not upload `.env` files to GitHub.

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_DISABLE_HASHING=false
APP_ENCRYPTION_KEY=<stable-production-secret>
OCR_SERVICE_BASE_URL=http://127.0.0.1:8002
OCR_SERVICE_VERIFY_ENDPOINT=/api/verify-document
```

Also configure the PostgreSQL, storage, CIMS, RPA, and OCR variables required by the application.

## First deployment checks

After deployment, check:

```text
https://your-domain.example/health
```

Then test the individual flow and inspect the container logs. The OCR service is internal, so it is not exposed as a public port.

The first OCR request may take longer while PaddleOCR downloads its model files. Use a persistent volume for model/cache data if the Dokploy setup recreates containers frequently.
