<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#133A1B',
                        secondary: '#B7BF96',
                        background: '#E4DEAE',
                        accent: '#8A9A5B'
                    }
                }
            }
        }
    </script>
    <style>
        .bg-transparent-bg {
            background-color: rgba(228, 222, 174, 0.1);
        }

        .geometric-card {
            clip-path: polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 0 100%);
        }

        .geometric-header {
            clip-path: polygon(0 0, 100% 0, calc(100% - 30px) 100%, 0 100%);
        }

        .signature-canvas {
            clip-path: polygon(10px 0, 100% 0, calc(100% - 10px) 100%, 0 100%);
        }
    </style>
</head>

<body class="bg-transparent-bg min-h-screen">
    @yield('content')
</body>

</html>
