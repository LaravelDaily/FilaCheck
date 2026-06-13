## laraveldaily/filacheck

- After you have created/modified any files in `app/Filament` folder, you must run `{{ $assist->binCommand('filacheck --fix') }}`, to ensure there is no deprecated Filament code. Reported not fixed issues MUST be fixed before continuing.