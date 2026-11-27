<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API REST v1 · Mesa de Partes Virtual</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>body { margin: 0; background: #fafafa; }</style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script>
        window.ui = SwaggerUIBundle({
            url: '{{ asset('docs/openapi.yaml') }}',
            dom_id: '#swagger-ui',
            deepLinking: true,
            persistAuthorization: true,
        });
    </script>
</body>
</html>
