<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🌐 {{ $service ? 'Edit' : 'Create' }} Service Card</h2>
    </x-slot>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('cms.index', ['tab' => 'services']) }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">← Back to Service Cards</a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $service ? 'Edit' : 'Create' }} Service Card</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ $service ? route('cms.services.update', $service) : route('cms.services.store') }}">
            @csrf
            @if ($service)
                @method('PUT')
            @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $service?->title ?? '') }}" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $service?->description ?? '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon Colour <span class="text-red-500">*</span></label>
                        <select name="icon" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach (\App\Models\SiteService::$iconPalette as $key => $palette)
                                <option value="{{ $key }}" @selected(old('icon', $service?->icon ?? 'emerald') === $key)>
                                    {{ ucfirst($key) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $service?->sort_order ?? 0) }}" min="0"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>

                @if ($service)
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="is_active" class="text-sm text-gray-700">Show this service card on the landing page</label>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700 transition">
                    {{ $service ? 'Update' : 'Create' }} Service Card
                </button>
                <a href="{{ route('cms.index', ['tab' => 'services']) }}" class="px-4 py-2.5 text-gray-600 rounded-lg font-medium hover:bg-gray-100 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
