<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinTrack API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            const params = new URLSearchParams(location.search);
            const sourceUrl = params.get('url') || '{{ url('/api-docs/swagger.json') }}';

            const ui = SwaggerUIBundle({
                url: sourceUrl,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: 'BaseLayout',
                tryItOutEnabled: true,
                requestInterceptor: (req) => {
                    // Attach Bearer token stored in localStorage if present
                    const token = localStorage.getItem('fintrack_bearer');
                    if (token && !req.headers['Authorization']) {
                        req.headers['Authorization'] = 'Bearer ' + token;
                    }
                    return req;
                }
            });
        };
    </script>
</body>
</html>