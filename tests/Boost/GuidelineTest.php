<?php

it('renders vendor/bin/filacheck when sail is disabled', function () {
    $rendered = $this->renderGuideline(false);

    expect($rendered)->toContain('vendor/bin/filacheck --fix');
});

it('renders vendor/bin/sail bin filacheck when sail is enabled', function () {
    $rendered = $this->renderGuideline(true);

    expect($rendered)->toContain('vendor/bin/sail bin filacheck --fix');
});
