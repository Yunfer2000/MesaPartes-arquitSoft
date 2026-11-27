{{-- CU13 Registrar respuesta y finalizar (área). --}}
<form method="POST" action="{{ route('area.responder', $expediente) }}" enctype="multipart/form-data" data-confirmar="¿Finalizar el expediente {{ $expediente->numero }}? Ya no se podrá derivar ni registrar avances.">
    @csrf
    <label for="contenido">Respuesta *</label>
    <textarea id="contenido" name="contenido" maxlength="5000">{{ old('contenido') }}</textarea>
    @error('contenido')<div class="error">{{ $message }}</div>@enderror
    <label for="documento">Documento de respuesta (opcional)</label>
    <div class="muted">Constancia, oficio o resolución; PDF o imagen. Si no adjuntas nada, el sistema igual genera el PDF de la respuesta con su código de verificación.</div>
    <input id="documento" type="file" name="documento" accept="application/pdf,image/jpeg,image/png">
    @error('documento')<div class="error">{{ $message }}</div>@enderror
    <div style="margin-top:10px"><button class="btn">Finalizar</button></div>
</form>
