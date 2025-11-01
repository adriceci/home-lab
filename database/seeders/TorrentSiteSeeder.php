<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class TorrentSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = [
            [
                'name' => '1337x',
                'url' => 'https://1337x.to',
                'description' => '1337x - Popular torrent search engine',
                'type' => 'torrent',
                'status' => 'active',
                'is_active' => true,
                'is_verified' => false,
            ],
            [
                'name' => 'ThePirateBay',
                'url' => 'https://1.piratebays.to',
                'description' => 'The Pirate Bay - Most popular torrent site',
                'type' => 'torrent',
                'status' => 'active',
                'is_active' => true,
                'is_verified' => false,
            ],
        ];

        foreach ($sites as $site) {
            // Check if site already exists
            $existing = Domain::where('name', $site['name'])
                ->where('type', 'torrent')
                ->first();

            if (!$existing) {
                Domain::create(array_merge($site, ['id' => Domain::generatePrefixedUuid()])); // Generate UUID manually since WithoutModelEvents disables model events
            } else {
                // Update existing site
                $existing->update($site);
            }
        }
    }
}
