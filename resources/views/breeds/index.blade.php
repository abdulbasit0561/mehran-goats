<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Breeds</h2>
            <a href="{{ route('breeds.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                + Add Breed
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                    <p class="text-sm text-gray-500">
                        Manage the goat breeds available when adding livestock.
                        Add as many as you need — they'll appear in the goat dropdown.
                    </p>
                </div>

                @forelse ($breeds as $breed)
                    <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-50 last:border-0">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-medium text-gray-900">{{ $breed->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $breed->goats_count > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $breed->goats_count }} goat{{ $breed->goats_count === 1 ? '' : 's' }}
                                </span>
                                @if ($breed->goats_count > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                        ♂ {{ $breed->male_goats_count }} male
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-700">
                                        ♀ {{ $breed->female_goats_count }} female
                                    </span>
                                @endif
                            </div>
                            @if ($breed->description)
                                <p class="mt-1 text-sm text-gray-500 truncate">{{ $breed->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 shrink-0 ml-4">
                            <a href="{{ route('breeds.edit', $breed) }}"
                               class="text-sm text-indigo-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('breeds.destroy', $breed) }}"
                                  onsubmit="return confirm('Delete breed &quot;{{ e($breed->name) }}&quot;?{{ $breed->goats_count > 0 ? ' This breed has goats assigned and cannot be deleted.' : '' }}')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="text-sm text-red-600 hover:underline disabled:text-gray-400 disabled:cursor-not-allowed"
                                        {{ $breed->goats_count > 0 ? 'disabled' : '' }}>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-4 sm:px-6 py-12 text-center">
                        <p class="text-gray-400 text-sm">No breeds yet. Click <span class="font-medium text-indigo-600">+ Add Breed</span> to create your first one.</p>
                    </div>
                @endforelse
            </div>

            <p class="mt-4 text-center text-sm text-gray-400">
                <a href="{{ route('goats.index') }}" class="hover:underline">← Back to Livestock</a>
            </p>
        </div>
    </div>
</x-app-layout>
