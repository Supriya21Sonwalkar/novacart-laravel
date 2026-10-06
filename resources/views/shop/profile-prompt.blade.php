@php($shoppingProfileComplete=\App\Services\CustomerProfile::complete(auth()->user()))
<dialog id="profile-prompt" aria-labelledby="profile-prompt-title" aria-describedby="profile-prompt-description" data-profile-complete="{{ $shoppingProfileComplete?'true':'false' }}">
 <button class="profile-prompt-close" type="button" data-close-profile aria-label="Close profile reminder">×</button>
 <span class="eyebrow muted">BEFORE YOU SHOP</span><h2 id="profile-prompt-title">Complete your profile first</h2>
 <p id="profile-prompt-description">{{ auth()->check()?'Please fill in your contact details and delivery address before adding products to your bag or wishlist, or placing an order.':'Please sign in and complete your contact details and delivery address before adding products to your bag or wishlist, or placing an order.' }}</p>
 <div class="profile-prompt-actions"><button class="outline" type="button" data-close-profile>Keep browsing</button><a class="primary" href="{{ auth()->check()?route('account'):route('login') }}">{{ auth()->check()?'Complete profile':'Sign in / Create account' }}</a></div>
</dialog>
<script src="/profile-prompt.js" defer></script>
