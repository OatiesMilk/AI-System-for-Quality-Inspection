<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Production Batch') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('manager.batches.update', $batch) }}">
                    @csrf
                    @method('PATCH')

                    <!-- Batch Code -->
                    <div>
                        <x-input-label for="batch_code" :value="__('Batch Code')" />
                        <x-text-input id="batch_code" class="block mt-1 w-full" type="text" name="batch_code" :value="old('batch_code', $batch->batch_code)" required autofocus placeholder="e.g. BATCH-1-20260701" />
                        <x-input-error :messages="$errors->get('batch_code')" class="mt-2" />
                    </div>

                    <!-- Production Date -->
                    <div class="mt-4">
                        <x-input-label for="production_date" :value="__('Production Date')" />
                        <x-text-input id="production_date" class="block mt-1 w-full" type="date" name="production_date" :value="old('production_date', $batch->production_date->toDateString())" required />
                        <x-input-error :messages="$errors->get('production_date')" class="mt-2" />
                    </div>

                    <!-- Shift -->
                    <div class="mt-4">
                        <x-input-label for="shift" :value="__('Shift')" />
                        <select id="shift" name="shift" required
                            class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" disabled {{ old('shift', $batch->shift) ? '' : 'selected' }}>{{ __('Select a shift') }}</option>
                            <option value="am" {{ old('shift', $batch->shift) === 'am' ? 'selected' : '' }}>{{ __('AM (Batch 1)') }}</option>
                            <option value="pm" {{ old('shift', $batch->shift) === 'pm' ? 'selected' : '' }}>{{ __('PM (Batch 2)') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('shift')" class="mt-2" />
                    </div>

                    <!-- Manufacturing Stage -->
                    <div class="mt-4">
                        <x-input-label for="manufacturing_stage" :value="__('Manufacturing Stage')" />
                        <select id="manufacturing_stage" name="manufacturing_stage" required
                            class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" disabled {{ old('manufacturing_stage', $batch->manufacturing_stage) ? '' : 'selected' }}>{{ __('Select a stage') }}</option>
                            <option value="preparation" {{ old('manufacturing_stage', $batch->manufacturing_stage) === 'preparation' ? 'selected' : '' }}>{{ __('Preparation') }}</option>
                            <option value="finishing" {{ old('manufacturing_stage', $batch->manufacturing_stage) === 'finishing' ? 'selected' : '' }}>{{ __('Finishing') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('manufacturing_stage')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('dashboard.manager') }}">
                            {{ __('Cancel') }}
                        </a>

                        <x-primary-button class="ms-4">
                            {{ __('Save Changes') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
