@props(['name', 'label', 'placeholder' => '', 'secret' => false, 'value' => ''])

<div>
    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
        {{ $label }}
    </label>
    <div style="position:relative">
        <input type="{{ $secret ? 'password' : 'text' }}"
               name="{{ $name }}"
               value="{{ $value }}"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               style="width:100%;padding:10px {{ $secret ? '40px' : '13px' }} 10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;box-sizing:border-box"
               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
               onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
        @if($secret)
        <button type="button"
                onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password';this.querySelector('svg').style.opacity=i.type==='text'?'1':'.45'"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:2px;color:var(--sa-text3)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:.45;transition:opacity 150ms">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
        @endif
    </div>
</div>
