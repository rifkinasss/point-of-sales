<?php

namespace Tests\Feature;

use App\Models\Store;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;
use Tests\TestCase;

class StoreLocationTest extends TestCase
{
    public function test_store_location_uses_the_project_latitude_and_longitude_columns(): void
    {
        $store = new Store;
        $store->location = new Coordinate(-1.2297453, 116.8398651);

        $this->assertArrayHasKey('latitude', $store->getAttributes());
        $this->assertArrayHasKey('longitude', $store->getAttributes());
        $this->assertArrayNotHasKey('lat', $store->getAttributes());
        $this->assertArrayNotHasKey('lng', $store->getAttributes());
    }
}
