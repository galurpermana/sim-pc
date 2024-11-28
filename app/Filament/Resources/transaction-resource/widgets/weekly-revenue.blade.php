<div>
    <div class="flex items-center gap-4 mb-4">
        <select
            wire:model="selectedMonth"
            class="block w-full px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        >
            @foreach ($this->getMonths() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-500">Weekly Revenue</h3>
            <p class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format($this->getStats()['weekly_revenue'], 2) }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-500">{{ Carbon\Carbon::parse($selectedMonth)->format('F Y') }} Revenue</h3>
            <p class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format($this->getStats()['monthly_revenue'], 2) }}</p>
        </div>
    </div>
</div>
