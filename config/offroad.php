<?php

return [
    'points_per_completed_trip' => (int) env('OFFROAD_POINTS_PER_COMPLETED_TRIP', 100),
    'rupiah_per_point' => (int) env('OFFROAD_RUPIAH_PER_POINT', 1000),
    'minimum_withdrawal_points' => (int) env('OFFROAD_MINIMUM_WITHDRAWAL_POINTS', 100),
];
