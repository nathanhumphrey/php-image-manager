<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Image API</title>
</head>

<body>
    <main>
        <h1>PHP Image API</h1>
        <form method="post" name="image-upload-form" action="/api/images/">
            <label for="image-upload">Upload an image</label>
            <input type="file" name="image-upload" id="image-upload" />
            <input type="submit" value="Upload" />
        </form>
    </main>
    <script>
        var form = document.querySelector('form[name="image-upload-form"]');
        form.addEventListener('submit', e => {
            e.preventDefault();
            var formData = new FormData(form);
            fetch('/api/images/', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
</body>

</html>