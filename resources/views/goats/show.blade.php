<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Goat Details — {{ $goat->tag_id }}</h2>
            <a href="{{ route('goats.index') }}" class="text-sm text-gray-500 hover:underline">← Back to Goats</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Main info card -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <div class="flex flex-wrap items-start gap-6">
                    @if ($goat->photo)
                        <img src="{{ Storage::url($goat->photo) }}" alt="Goat {{ $goat->tag_id }}" class="w-32 h-32 rounded-lg object-cover" />
                    @else
                        <div class="w-32 h-32 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-4xl">🐐</div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Tag</p>
                                <p class="font-medium text-gray-900">{{ $goat->tag_id }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Name</p>
                                <p class="font-medium text-gray-900">{{ $goat->name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Breed</p>
                                <p class="font-medium text-gray-900">{{ $goat->breed->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Gender</p>
                                <p class="font-medium text-gray-900 capitalize">{{ $goat->gender }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Date of Birth</p>
                                <p class="font-medium text-gray-900">{{ $goat->dob?->format('M d, Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Age</p>
                                <p class="font-medium text-gray-900">{{ $goat->age_in_months ? $goat->age_in_months . ' months' : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Weight</p>
                                <p class="font-medium text-gray-900">{{ number_format($goat->weight_kg, 1) }} kg</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Status</p>
                                @php
                                    $statusColors = [
                                        'deceased' => 'bg-gray-200 text-gray-700',
                                        'sold'     => 'bg-blue-100 text-blue-800',
                                        'breeder'  => 'bg-green-100 text-green-800',
                                    ];
                                    $statusCls = $statusColors[$goat->status] ?? 'bg-yellow-100 text-yellow-800';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusCls }}">{{ ucfirst($goat->status) }}</span>
                                @php $activePreg = $goat->activePregnancy(); @endphp
                                @if ($activePreg)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-800 ml-1">
                                        🤰 {{ $activePreg->isConfirmed() ? 'Pregnant' : 'Bred' }} ({{ $activePreg->days_pregnant }}d)
                                    </span>
                                @endif
                            </div>
                            @if ($goat->isFarmBorn())
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Birth</p>
                                    <p class="text-sm text-gray-700">Farm-born</p>
                                    @if ($goat->sire || $goat->dam)
                                        <p class="text-xs text-gray-400">
                                            @if ($goat->sire)<a href="{{ route('goats.show', $goat->sire) }}" class="hover:text-indigo-600">Sire: {{ $goat->sire->tag_id }}</a>@endif
                                            @if ($goat->sire && $goat->dam) · @endif
                                            @if ($goat->dam)<a href="{{ route('goats.show', $goat->dam) }}" class="hover:text-indigo-600">Dam: {{ $goat->dam->tag_id }}</a>@endif
                                        </p>
                                    @endif
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Caretaker</p>
                                @if ($goat->caretaker)
                                    <a href="{{ route('caretakers.show', $goat->caretaker) }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                                        🤝 {{ $goat->caretaker->name }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Purchase Price</p>
                                <p class="font-medium text-gray-900">{{ number_format($goat->purchase_price, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Sale Price</p>
                                <p class="font-medium text-gray-900">{{ number_format($goat->sale_price, 2) }}</p>
                            </div>
                        </div>

                        @if ($goat->notes)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs text-gray-500 uppercase mb-1">Notes</p>
                                <p class="text-sm text-gray-700">{{ $goat->notes }}</p>
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('goats.edit', $goat) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Edit</a>
                            @if ($goat->status !== 'deceased')
                                <a href="{{ route('goats.declare-dead', $goat) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200">Declare Dead</a>
                            @endif
                            <form method="POST" action="{{ route('goats.destroy', $goat) }}" onsubmit="return confirm('Delete this goat?')">
                                @csrf @method('DELETE')
                                <button class="px-4 py-2 bg-red-50 text-red-700 rounded-md text-sm font-medium hover:bg-red-100">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sale record (shown only when one exists) --}}
            @if ($goat->sale)
                @php $sale = $goat->sale->load('customer', 'seller'); @endphp
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="font-semibold text-gray-900">💰 Sale record</h3>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-gray-500">Paid: <strong class="text-gray-900">{{ number_format($sale->paid_amount, 0) }}</strong></span>
                            <span class="text-gray-500">Balance: <strong class="{{ $sale->balance_due > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ number_format($sale->balance_due, 0) }}</strong></span>
                            @if ($sale->status === 'paid')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                            @elseif ($sale->status === 'partial')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Partial</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>
                            @endif
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Sold on</dt>
                            <dd class="mt-1">{{ $sale->sale_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Buyer</dt>
                            <dd class="mt-1">
                                @if ($sale->customer)
                                    <a href="{{ route('customers.show', $sale->customer) }}" class="text-indigo-600 hover:underline">{{ $sale->customer->name }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Sold by</dt>
                            <dd class="mt-1">{{ $sale->seller?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase">Price</dt>
                            <dd class="mt-1 font-bold text-gray-900">{{ number_format((float) $sale->sale_price, 0) }}</dd>
                        </div>
                    </dl>

                    @if ($sale->photo_path)
                        <a href="{{ Storage::url($sale->photo_path) }}" target="_blank" class="mt-4 inline-block">
                            <img src="{{ Storage::url($sale->photo_path) }}" alt="Goat at sale" class="h-24 w-24 object-cover rounded-md border" />
                        </a>
                    @endif

                    <a href="{{ route('sales.show', $sale) }}" class="mt-4 inline-block text-sm text-indigo-600 hover:underline">View full sale & payments →</a>
                </div>
            @endif

            {{-- Death record (shown only when one exists) --}}
            @if ($goat->deathRecord)
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">⚰️ Death Record</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700">Deceased</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Date of Death</p>
                            <p class="font-medium text-gray-900">{{ $goat->deathRecord->death_date->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Cause</p>
                            <p class="font-medium text-gray-900">{{ $goat->deathRecord->cause ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Disposal Method</p>
                            <p class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $goat->deathRecord->disposal_method) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Disposal Value</p>
                            <p class="font-medium text-gray-900">{{ number_format($goat->deathRecord->disposal_value, 2) }}</p>
                        </div>
                    </div>
                    @if ($goat->deathRecord->notes)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-500 uppercase mb-1">Notes</p>
                            <p class="text-sm text-gray-700">{{ $goat->deathRecord->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============================= --}}
            {{--       IMAGE GALLERY          --}}
            {{-- ============================= --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-900">📸 Photo Gallery <span class="text-sm font-normal text-gray-400">({{ $goat->images->count() }})</span></h3>
                </div>

                {{-- Gallery grid --}}
                @if ($goat->images->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-6">
                        @foreach ($goat->images as $img)
                            <div class="group relative">
                                <button type="button"
                                    onclick="openLightbox('{{ Storage::url($img->image_path) }}', {{ json_encode($img->age_months) }}, {{ json_encode($img->weight_kg) }}, '{{ $img->taken_at->format('M d, Y') }}', {{ json_encode($img->notes ?? '') }})"
                                    class="block w-full">
                                    <img src="{{ Storage::url($img->image_path) }}" alt="Goat photo" class="w-full aspect-square object-cover rounded-lg hover:opacity-80 transition" />
                                </button>
                                {{-- Metadata badge --}}
                                <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-2 py-1 rounded-b-lg truncate">
                                    {{ $img->taken_at->format('M d') }} · {{ $img->age_months }}mo · {{ $img->weight_kg }}kg
                                </div>
                                {{-- Delete button --}}
                                <form method="POST" action="{{ route('goats.images.destroy', $img) }}" onsubmit="return confirm('Delete this photo?')" class="absolute top-1 right-1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-600/80 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600" title="Delete">&times;</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 mb-4">No photos yet. Upload or capture a photo below.</p>
                @endif

                {{-- Upload form --}}
                <form method="POST" action="{{ route('goats.images.store', $goat) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-sm font-medium text-gray-700 mb-3">Add Photo to Gallery</p>

                        @if ($errors->any())
                            <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                                <ul class="text-sm text-red-700 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Mode toggle --}}
                        <div class="flex gap-2 mb-3">
                            <button type="button" onclick="document.getElementById('gallery-image').classList.remove('hidden'); document.getElementById('gallery-capture-wrap').classList.add('hidden'); this.classList.add('bg-indigo-600','text-white'); this.classList.remove('bg-indigo-50','text-indigo-700'); document.getElementById('btn-gallery-capture').classList.add('bg-indigo-50','text-indigo-700'); document.getElementById('btn-gallery-capture').classList.remove('bg-indigo-600','text-white');"
                                id="btn-gallery-upload" class="px-3 py-1.5 rounded text-xs font-medium bg-indigo-600 text-white">
                                📁 Upload Image
                            </button>
                            <button type="button" onclick="document.getElementById('gallery-image').classList.add('hidden'); document.getElementById('gallery-capture-wrap').classList.remove('hidden'); this.classList.add('bg-indigo-600','text-white'); this.classList.remove('bg-indigo-50','text-indigo-700'); document.getElementById('btn-gallery-upload').classList.add('bg-indigo-50','text-indigo-700'); document.getElementById('btn-gallery-upload').classList.remove('bg-indigo-600','text-white');"
                                id="btn-gallery-capture" class="px-3 py-1.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700">
                                📷 Capture via Camera
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Left: image selection --}}
                            <div>
                                {{-- Single file input shared by upload + capture modes --}}
                                <input id="gallery-image" name="image" type="file" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />

                                {{-- Capture mode: camera button + preview (hidden by default) --}}
                                <div id="gallery-capture-wrap" class="hidden mt-2">
                                    <div class="flex items-center gap-3">
                                        <button type="button" onclick="openCamera('gallery-image', 'gallery-capture-preview')"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                                            📷 Open Camera
                                        </button>
                                        <img id="gallery-capture-preview" src="" alt="" class="hidden w-20 h-20 rounded-lg object-cover" />
                                    </div>
                                </div>
                            </div>

                            {{-- Right: required metadata --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Age (months) *</label>
                                    <input name="age_months" type="number" step="0.1" min="0" required value="{{ old('age_months', $goat->age_in_months ?? 0) }}" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Weight (kg) *</label>
                                    <input name="weight_kg" type="number" step="0.01" min="0" required value="{{ old('weight_kg', $goat->weight_kg ?? 0) }}" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Date *</label>
                                    <input name="taken_at" type="date" required value="{{ old('taken_at', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 uppercase">Notes</label>
                                    <input name="notes" type="text" maxlength="1000" value="{{ old('notes') }}" placeholder="Optional" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Add Photo</button>
                        </div>
                    </div>
                </form>
            </div>
            {{-- ====== END IMAGE GALLERY ====== --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Health records -->
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Health Records</h3>
                        <a href="{{ route('goats.health-records.create', $goat) }}" class="text-xs text-indigo-600 hover:underline">+ Add</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($goat->healthRecords as $record)
                            <div class="border-l-2 border-indigo-200 pl-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 capitalize">{{ $record->type }}</p>
                                        <p class="text-xs text-gray-500">{{ $record->date->format('M d, Y') }} · {{ number_format($record->cost, 2) }}</p>
                                        @if ($record->notes) <p class="text-xs text-gray-600 mt-1">{{ $record->notes }}</p> @endif
                                    </div>
                                    <form method="POST" action="{{ route('health-records.destroy', $record) }}" onsubmit="return confirm('Delete record?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 text-xs">×</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No health records.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Breeding records -->
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Breeding Records</h3>
                        <a href="{{ route('goats.breeding-records.create', $goat) }}" class="text-xs text-indigo-600 hover:underline">+ Add</a>
                    </div>

                    {{-- Active pregnancy banner --}}
                    @if ($goat->gender === 'female' && $activePreg)
                        <div class="mb-4 rounded-lg bg-pink-50 border border-pink-200 p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-pink-800">
                                        🤰 {{ $activePreg->isConfirmed() ? 'Pregnancy Confirmed' : 'Bred (Unconfirmed)' }}
                                    </p>
                                    <p class="text-xs text-pink-600 mt-1">
                                        Bred {{ $activePreg->breeding_date->format('M d, Y') }} · {{ $activePreg->days_pregnant }} days pregnant
                                    </p>
                                    <p class="text-xs text-pink-600">
                                        Expected kidding: <strong>{{ $activePreg->expected_kidding_date->format('M d, Y') }}</strong>
                                        @if ($activePreg->days_until_due >= 0)({{ $activePreg->days_until_due }} days left) @else(OVERDUE)@endif
                                    </p>
                                    <div class="mt-1 w-full bg-pink-100 rounded-full h-1">
                                        <div class="bg-pink-400 h-1 rounded-full" style="width: {{ $activePreg->pregnancy_progress }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if (! $activePreg->isConfirmed())
                                    <form method="POST" action="{{ route('breeding-records.mark-pregnant', $activePreg) }}">
                                        @csrf
                                        <button class="px-2.5 py-1 bg-pink-600 text-white rounded text-xs font-medium hover:bg-pink-700">✓ Confirm Pregnancy</button>
                                    </form>
                                @endif
                                <a href="{{ route('breeding-records.kidding', $activePreg) }}" class="px-2.5 py-1 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">👶 Record Kidding</a>
                                <form method="POST" action="{{ route('breeding-records.mark-open', $activePreg) }}">
                                    @csrf
                                    <button class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded text-xs font-medium hover:bg-gray-200" onclick="return confirm('Mark as did-not-conceive?')">✗ Open</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @php
                            $allBreeding = $goat->siredRecords->merge($goat->birthedRecords)->unique('id')->sortByDesc('breeding_date');
                        @endphp
                        @forelse ($allBreeding as $record)
                            <div class="border-l-2 pl-3 {{ $record->pregnancy_status === 'pregnant' ? 'border-pink-400' : ($record->pregnancy_status === 'kidded' ? 'border-green-400' : ($record->pregnancy_status === 'bred' ? 'border-yellow-400' : 'border-gray-200')) }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $record->breeding_date->format('M d, Y') }}
                                            @if ($record->pregnancy_status !== 'kidded')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs ml-1
                                                    {{ $record->pregnancy_status === 'pregnant' ? 'bg-pink-100 text-pink-700' : '' }}
                                                    {{ $record->pregnancy_status === 'bred' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                    {{ $record->pregnancy_status === 'open' ? 'bg-gray-100 text-gray-500' : '' }}
                                                    {{ $record->pregnancy_status === 'aborted' ? 'bg-red-100 text-red-700' : '' }}">
                                                    {{ ucfirst($record->pregnancy_status) }}
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Sire: {{ $record->sire->tag_id }} · Dam: {{ $record->dam->tag_id }}
                                        </p>
                                        @if ($record->kidding_date)
                                            <p class="text-xs text-gray-600">
                                                Kidded: {{ $record->kidding_date->format('M d, Y') }} · {{ $record->litter_size }} kids
                                                @if ($record->kidding_difficulty) · {{ ucfirst($record->kidding_difficulty) }} @endif
                                            </p>
                                            @if ($record->kids->isNotEmpty())
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($record->kids as $kid)
                                                        <a href="{{ route('goats.show', $kid) }}" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs {{ $kid->gender === 'female' ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' }} hover:underline">
                                                            🐐 {{ $kid->tag_id }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @elseif ($record->expected_kidding_date)
                                            <p class="text-xs text-gray-400">
                                                Expected: {{ $record->expected_kidding_date->format('M d, Y') }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('breeding-records.edit', $record) }}" class="text-gray-400 hover:text-indigo-600 text-xs" title="Edit">✎</a>
                                        <form method="POST" action="{{ route('breeding-records.destroy', $record) }}" onsubmit="return confirm('Delete record?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-500 text-xs">×</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No breeding records.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Ownership -->
                <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Ownership</h3>
                    </div>
                    <div class="space-y-3">
                        @forelse ($goat->ownershipUnits as $unit)
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $unit->investor->name }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($unit->share_pct, 2) }}% share</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No ownership assigned. <a href="{{ route('investors.index') }}" class="text-indigo-600 hover:underline text-xs">Assign in Investors</a></p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{--     LIGHTBOX PHOTO VIEWER    --}}
    {{-- ============================= --}}
    <div id="lightbox" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4" onclick="closeLightbox(event)">
        <div class="relative max-w-3xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">📸 Photo Details</h3>
                <button type="button" onclick="closeLightbox()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <div class="max-h-[55vh] overflow-hidden bg-gray-100 flex items-center justify-center">
                <img id="lightbox-img" src="" alt="Goat photo" class="max-h-[55vh] w-full object-contain" />
            </div>
            <div class="px-5 py-4">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Age</p>
                        <p id="lightbox-age" class="text-lg font-semibold text-gray-900">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Weight</p>
                        <p id="lightbox-weight" class="text-lg font-semibold text-gray-900">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Date</p>
                        <p id="lightbox-date" class="text-lg font-semibold text-gray-900">—</p>
                    </div>
                </div>
                <div id="lightbox-notes-wrap" class="hidden mt-3 pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400 uppercase mb-1">Notes</p>
                    <p id="lightbox-notes" class="text-sm text-gray-700"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Camera capture modal --}}
    @include('goats.partials._camera-capture')

    {{-- Lightbox JS --}}
    @push('scripts')
        <script>
            function openLightbox(src, age, weight, date, notes) {
                document.getElementById('lightbox-img').src = src;
                document.getElementById('lightbox-age').textContent = age ? age + ' months' : '—';
                document.getElementById('lightbox-weight').textContent = weight ? weight + ' kg' : '—';
                document.getElementById('lightbox-date').textContent = date || '—';
                if (notes) {
                    document.getElementById('lightbox-notes').textContent = notes;
                    document.getElementById('lightbox-notes-wrap').classList.remove('hidden');
                } else {
                    document.getElementById('lightbox-notes-wrap').classList.add('hidden');
                }
                document.getElementById('lightbox').classList.remove('hidden');
            }

            function closeLightbox(event) {
                if (event && event.target !== document.getElementById('lightbox')) return;
                document.getElementById('lightbox').classList.add('hidden');
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeLightbox();
                }
            });
        </script>
    @endpush
</x-app-layout>
