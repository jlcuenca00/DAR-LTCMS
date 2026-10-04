@once
    <script>
        // Server-returned input takes precedence over a browser draft.
        window.darFormDraftContext = { hasOldInput: @js(session()->hasOldInput()) };
    </script>
@endonce
