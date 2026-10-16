<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
       
<div class="mt-4">
    <x-input-label for="role" value="Register As" />

    <select id="role" name="role" required
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        <option default value="customer" @selected(old('role') === 'customer')>
            Customer
        </option>
        <option value="business_owner" @selected(old('role') === 'business_owner')>
            Business Owner
        </option>
    </select>

    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>


        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

<!-- Login Link and Submit Button -->
<div class="flex items-center justify-end mt-4">
    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
       href="{{ route('login') }}">
        {{ __('Already registered? Login') }}
    </a>

    <x-primary-button id="register-button" class="ms-4">
        Register
    </x-primary-button>
</div>

<!-- Change button text based on selected role -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        const registerButton = document.getElementById('register-button');

        function updateButtonText() {
            registerButton.textContent =
                roleSelect.value === 'business_owner'
                    ? 'Continue to Shop Registration'
                    : 'Register';
        }

        roleSelect.addEventListener('change', updateButtonText);
        updateButtonText();
    });
</script>
</form>
</x-guest-layout>