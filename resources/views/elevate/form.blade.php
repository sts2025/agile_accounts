<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elevate Privileges</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl border border-indigo-100 w-full max-w-md">
        <div class="text-center mb-6">
             <svg class="mx-auto h-12 w-12 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-1.586l.293-.293A1 1 0 0019 19.586V7.414a1 1 0 00-.293-.707l-.707-.707A1 1 0 0018 6.414V5a2 2 0 00-2-2H8a2 2 0 00-2 2v1.414a1 1 0 00-.293.707l-.707.707A1 1 0 004 7.414v12.172a1 1 0 00.293.707l.707.707z" />
            </svg>
            <h2 class="text-2xl font-extrabold text-gray-900 mt-3">Access Control</h2>
        </div>
        
        <!-- Display Success/Error Messages -->
        @if (session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-lg mb-4" role="alert">
                <p class="font-bold">Success!</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-lg mb-4" role="alert">
                <p class="font-bold">Access Denied</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif
        
        <p class="text-gray-600 mb-6 text-center text-sm">
            This section requires a special password for critical operations.
        </p>

        <form method="POST" action="{{ route('elevate.process') }}">
            @csrf

            <div class="mb-6">
                <label for="elevation_password" class="block text-gray-700 text-sm font-medium mb-2">
                    Elevation Password
                </label>
                <input 
                    type="password" 
                    id="elevation_password" 
                    name="elevation_password" 
                    required 
                    autocomplete="off"
                    class="appearance-none border border-gray-300 rounded-xl w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out shadow-sm"
                >
                @error('elevation_password')
                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-center">
                <button 
                    type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition duration-150 ease-in-out shadow-md hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50"
                >
                    Unlock Privileges
                </button>
            </div>
        </form>
    </div>

</body>
</html>