<?php
function tvr_get_config() {
    return [
        'post_types' => [
            'st_tours' => 'Tour',
            'st_activity' => 'Activity',
            'st_hotel' => 'Hotel',
            'st_rental' => 'Rental',
        ],
        'languages' => [
            'en' => [
                'name' => 'English',
                'tour' => [
                    'Our guide was fantastic and made the tour in %s unforgettable!',
                    'An amazing experience exploring %s with a great team!',
                    'Loved every moment of this tour, highly recommend %s!',
                ],
                'activity' => [
                    'Had a fantastic time with this activity in %s!',
                    'An exciting and memorable activity experience in %s!',
                    'Highly recommend this activity in %s!',
                    'Amazing staff and fun from start to finish in %s!',
                    'Perfect way to spend the day in %s, will do again!'
                ],
                'hotel' => [
                    'The stay at this %s hotel was phenomenal, perfect comfort!',
                    'Loved the cleanliness and service at %s, a true gem!',
                    'Amazing rooms and location at %s, highly recommend!',
                ],
                'ending' => [
                    'Will definitely come back!',
                    'Highly recommend to everyone!',
                    'A wonderful experience!',
                ],
                'features' => [
                    'scenery',
                    'tour guide',
                    'activities',
                    'culture',
                    'views',
                    'wine',
                    'location',
                ],
            ],
            // Example for Spanish (add to all other languages you want to support)
            'es' => [
                'name' => 'Spanish',
                'tour' => [
                    '¡Nuestro guía fue fantástico e hizo que el tour en %s fuera inolvidable!',
                    '¡Una experiencia increíble explorando %s con un gran equipo!',
                    '¡Amé cada momento de este tour, altamente recomendado %s!',
                ],
                'activity' => [
                    '¡Actividad súper divertida en %s, lo recomiendo mucho!',
                    'Una experiencia emocionante y memorable en %s.',
                    '¡El mejor plan para pasar el día en %s, repetiremos seguro!',
                ],
                'hotel' => [
                    '¡La estancia en este hotel en %s fue fenomenal, máximo confort!',
                    '¡Me encantó la limpieza y el servicio en %s, una joya!',
                    '¡Habitaciones y ubicación increíbles en %s, muy recomendado!',
                ],
                'ending' => [
                    '¡Definitivamente volveré!',
                    '¡Altamente recomendado para todos!',
                    '¡Una experiencia maravillosa!',
                ],
                'features' => [
                    'paisaje',
                    'hospitalidad',
                    'actividades',
                    'cultura',
                    'sueño',
                    'limpieza',
                    'habitaciones',
                ],
            ],
            // Repeat for every other language: copy 'tour' structure to 'activity'
            // ... (other languages)
        ],
        'reviewer_types' => [
            'Solo traveler',
            'Couple',
            'Family',
            'Group',
        ],
        'seasons' => [
            'week',
            'day',
            'month',
            '2 days',
        ],
        'tones' => [
            'enthusiastic',
            'calm',
            'concise',
            'detailed',
        ],
        'rating_meta_key' => 'st_review_stats',
        'review_criteria' => [
            'st_tours' => [
                'Tour Guide' => 5,
                'Location' => 5,
                'Service' => 5,
                'Friendliness' => 5,
                'Overall' => 5,
            ],
            'st_activity' => [
                'Location' => 5,
                'Atmosphere' => 5,
                'Overall' => 5,
                'Experience' => 5,
                'Tour Guide' => 5,
                'Enjoyability' => 5,
            ],
            'st_hotel' => [
                'Sleep' => 5,
                'Location' => 5,
                'Service' => 5,
                'Cleanliness' => 5,
                'Rooms' => 5,
            ],
            'st_rental' => [
                'Sleep' => 5,
                'Location' => 5,
                'Service' => 5,
                'Cleanliness' => 5,
                'Rooms' => 5,
            ],
        ],
    ];
}