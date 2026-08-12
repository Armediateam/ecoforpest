<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <div class="mb-6">
            <svg class="w-16 h-16 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Payment Failed</h1>
        <p class="text-gray-600 mb-6">We're sorry, but your payment could not be processed. Please try again or contact support if the problem persists.</p>
        <div class="space-y-3">
            <a href="/" class="inline-block bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600 transition-colors">
                Return to Homepage
            </a>
            <div>
                <a href="mailto:support@example.com" class="text-red-500 hover:text-red-600 text-sm">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</body>
</html>
