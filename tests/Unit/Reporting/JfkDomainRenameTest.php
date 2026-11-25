<?php

use App\Services\Reporting\Reporting;

/**
 * JFK moved jfk.men -> jfk.nl. Partner exports now say jfk.nl; old months still
 * say jfk.men and must keep matching when reprocessed.
 */
it('matches JFK on the new jfk.nl host', function () {
    expect(Reporting::siteHostMatches('jfk.nl', 'jfk'))->toBeTrue()
        ->and(Reporting::siteHostMatches('www.JFK.nl', 'jfk'))->toBeTrue();
});

it('still matches JFK on the old jfk.men host', function () {
    expect(Reporting::siteHostMatches('jfk.men', 'jfk'))->toBeTrue()
        ->and(Reporting::siteHostMatches('JFK.men', 'jfk'))->toBeTrue();
});

it('does not fold look-alike hosts into JFK', function () {
    expect(Reporting::siteHostMatches('notjfk.nl', 'jfk'))->toBeFalse()
        ->and(Reporting::siteHostMatches('f1maximaal.nl', 'jfk'))->toBeFalse();
});
