<?php

return [

    // EXACT category names in your DB/UI
    'allowed' => [
        'Flower',
        'Joints',
        'Extract',
        'Concentrate',
        'Infused Joints',
        'Extract Carts',
        'Flavored Carts',
        'Edibles',
        'Drinks/Tinctures',
        'Clones',
        'Accessories',
        'Apparel',
        'Hemp',
        'Topicals',
    ],

    // CSV text → exact category
    'synonyms' => [
        'flower'            => 'Flower',
        'flowers'           => 'Flower',

        'joint'             => 'Joints',
        'joints'            => 'Joints',
        'pre roll'          => 'Joints',
        'pre-roll'          => 'Joints',
        'pre rolls'         => 'Joints',
        'pre-rolls'         => 'Joints',

        'infused joint'     => 'Infused Joints',
        'infused joints'    => 'Infused Joints',
        'infused pre roll'  => 'Infused Joints',
        'infused pre-roll'  => 'Infused Joints',

        'concentrate'       => 'Concentrate',
        'concentrates'      => 'Concentrate',

        'extract'           => 'Extract',
        'extracts'          => 'Extract',

        'cartridge'         => 'Extract Carts',
        'cartridges'        => 'Extract Carts',
        'cart'              => 'Extract Carts',
        'carts'             => 'Extract Carts',
        'vape'              => 'Extract Carts',
        'vapes'             => 'Extract Carts',

        'flavored carts'    => 'Flavored Carts',
        'flavored cart'     => 'Flavored Carts',

        'edible'            => 'Edibles',
        'edibles'           => 'Edibles',

        'drink'             => 'Drinks/Tinctures',
        'drinks'            => 'Drinks/Tinctures',
        'tincture'          => 'Drinks/Tinctures',
        'tinctures'         => 'Drinks/Tinctures',
        'beverage'          => 'Drinks/Tinctures',
        'beverages'         => 'Drinks/Tinctures',

        'clones'            => 'Clones',
        'clone'             => 'Clones',
        'accessories'       => 'Accessories',
        'apparel'           => 'Apparel',
        'hemp'              => 'Hemp',
        'topicals'          => 'Topicals',
    ],

    // Phrases that force Flavored Carts
    'flavored_cart_signals' => [
        'buddies flavored',
        'green leaf special',
        'gls',
    ],

    // PRIORITIZED keyword rules (first match wins)
    // Flower is LAST so carts can’t fall into Flower.
    'rules' => [
        // flavored carts first
        ['rx' => '/\b(flavor(?:ed)?|terp(?:s|ene)?s?)\b.*\b(cartridge|cart\b|510|vape|disposable|pod|pax|stiiizy|airgraft)\b/i', 'cat' => 'Flavored Carts'],
        ['rx' => '/\bbuddies\s+flavor(?:ed)?\b.*\b(cartridge|cart\b|510|vape|disposable|pod)\b/i', 'cat' => 'Flavored Carts'],

        // generic carts
        ['rx' => '/\b(cartridge|cart\b|510|vapes?|vape[- ]?pen|disposable|aio|all[- ]in[- ]one|pod|pods|pax|stiiizy|airgraft)\b/i', 'cat' => 'Extract Carts'],

        // infused prerolls beat joints
        ['rx' => '/\b(infused|diamond|hash|k?ief|rosin)\b.*\b(pre[- ]?rolls?|joints?)\b/i', 'cat' => 'Infused Joints'],
        // joints
        ['rx' => '/\b(pre[- ]?rolls?|joints?)\b/i', 'cat' => 'Joints'],

        // dabbables (NOT carts)
        ['rx' => '/\b(budder|badder|sugar|crumble|hash(?!\s*infused)|rosin(?!.*cart)|wax|shatter|diamonds?|sauce|live\s*resin|live\s*rosin)\b/i', 'cat' => 'Concentrate'],

        // edibles
        ['rx' => '/\b(edible|gummy|choc(?:olate)?|cookie|brownie|chew|candy|lozenge|mint|baked)\b/i', 'cat' => 'Edibles'],

        // drinks / tinctures
        ['rx' => '/\b(tincture|beverage|drink|soda|elixir|lemonade|tea|coffee|shot)\b/i', 'cat' => 'Drinks/Tinctures'],

        // topicals
        ['rx' => '/\b(topical|lotion|balm|salve|cream|ointment|patch)\b/i', 'cat' => 'Topicals'],

        // clones, accessories, apparel, hemp
        ['rx' => '/\b(clone|clones|seed|seeds|plant)\b/i', 'cat' => 'Clones'],
        ['rx' => '/\b(battery|charger|lighter|papers?|cones?|tips?|grinder|bong|rig|tray|torch)\b/i', 'cat' => 'Accessories'],
        ['rx' => '/\b(tee|t[- ]?shirt|shirt|hoodie|sweatshirt|hat|cap|beanie)\b/i', 'cat' => 'Apparel'],
        ['rx' => '/\bhemp\b/i', 'cat' => 'Hemp'],

        // flower last
        ['rx' => '/\b(flower|bud|smalls|popcorn|eighth|quarter|half|ounce|oz|pre[- ]?pack)\b/i', 'cat' => 'Flower'],
    ],
];
