<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Client Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Client Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-bold text-indigo-600 mb-1">{{ $client->name }}</h3>
                            <p class="text-sm text-gray-500">Client ID: #{{ $client->id }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                            Active
                        </span>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contact Information -->
                        <div>
                            <h4 class="font-semibold text-gray-700 border-b pb-2 mb-3">Contact Info</h4>
                            <p class="mb-2"><span class="font-medium">Phone:</span> {{ $client->phone_number }}</p>
                            <p class="mb-2"><span class="font-medium">Email:</span> {{ $client->email ?? 'N/A' }}</p>
                            <p class="mb-2"><span class="font-medium">Address:</span> {{ $client->address }}</p>
                        </div>

                        <!-- Personal Details -->
                        <div>
                            <h4 class="font-semibold text-gray-700 border-b pb-2 mb-3">Personal Details</h4>
                            <p class="mb-2"><span class="font-medium">National ID:</span> {{ $client->national_id ?? 'N/A' }}</p>
                            <p class="mb-2"><span class="font-medium">Occupation:</span> {{ $client->occupation ?? 'N/A' }}</p>
                            <p class="mb-2"><span class="font-medium">Date of Birth:</span> {{ $client->date_of_birth ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-8 flex flex-wrap gap-3 border-t pt-6">
                        <a href="{{ route('clients.edit', $client) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                            Edit Profile
                        </a>
                        <a href="{{ route('clients.ledger', $client) }}" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                            View Ledger
                        </a>
                        <a href="{{ route('clients.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 transition">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity / Loans Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Loan History</h3>
                    
                    @if($client->loans->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Given</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($client->loans as $loan)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #{{ $loan->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ number_format($loan->principal_amount) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $loan->start_date }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $loan->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ ucfirst($loan->status ?? 'active') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 italic">No loans found for this client.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>