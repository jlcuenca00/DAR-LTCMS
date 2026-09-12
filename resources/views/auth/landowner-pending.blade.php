<x-guest-layout>
    <div class="space-y-5 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-amber-100 text-amber-700">
            <i class="fa-solid fa-hourglass-half text-xl"></i>
        </div>

        <div>
            <h2 class="text-xl font-black text-gray-900">Registration received</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">
                Your landowner account is waiting for DAR staff review. You can sign in, but land records, parcel maps, and clearance outputs will stay locked until your identity is verified and your account is linked to the correct landowner record.
            </p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-left text-sm leading-6 text-amber-900">
            <strong>Next step:</strong> please wait for DAR staff confirmation, or visit/contact the DAR Negros Oriental Provincial Office if they need to verify your details.
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-green-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-800">
                Sign out
            </button>
        </form>
    </div>
</x-guest-layout>
