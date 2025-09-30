<div class="p-4">
    <!-- Search -->
    <div class="mb-4">
        <input type="text" wire:model.debounce.500ms="search"
            class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200"
            placeholder="Search...">
    </div>

    <!-- Filters -->
    <div class="flex gap-3 mb-4">
        @foreach($filters as $key => $options)
            <select wire:model="selectedFilters.{{ $key }}"
                class="rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200">
                <option value="">All {{ ucfirst($key) }}</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        @endforeach
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="{{ $config['class'] }}">
            <thead class="bg-gray-100">
                <tr>
                    @foreach($columns as $col)
                        <th wire:click="sortBy('{{ $col }}')"
                            class="px-4 py-2 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider cursor-pointer select-none">
                            {{ ucfirst($col) }}
                            @if($sortField === $col)
                                <span class="ml-1">{!! $sortDirection === 'asc' ? '▲' : '▼' !!}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        @foreach($columns as $col)
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $row->$col }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-4 py-2 text-center text-gray-500">
                            No results found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination or Load More -->
    <div class="mt-4 text-center">
        @if($paginationMode === 'load-more')
            @if($rows->count() >= $limit)
                <button wire:click="loadMore" class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700">
                    Load More
                </button>
            @endif
        @else
            {{ $rows->links() }}
        @endif
    </div>
</div>
