@props(['name' => null, 'checked' => false, 'value' => '1'])

{{--
    Toggle (switch) acessível. Importante: usar UM ÚNICO atributo class.
    Dois atributos class no mesmo elemento fazem o navegador honrar apenas o
    primeiro, descartando "is-on" — por isso o estado ligado não aparecia.
--}}
<button type="button"
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        onclick="
            const on = this.classList.toggle('is-on');
            this.setAttribute('aria-checked', on ? 'true' : 'false');
            const inp = this.querySelector('input[type=hidden]');
            if (inp) inp.value = on ? inp.dataset.onValue : '0';
            this.dispatchEvent(new CustomEvent('sa-toggle', {
                bubbles: true, detail: { name: inp ? inp.name : null, on }
            }));
        "
        {{ $attributes->merge(['class' => 'sa-toggle'.($checked ? ' is-on' : '')]) }}>
    @if($name)
    <input type="hidden" name="{{ $name }}" value="{{ $checked ? $value : '0' }}"
           data-on-value="{{ $value }}">
    @endif
    <span class="sa-toggle__knob"></span>
</button>
