<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\Withdrawal;
use App\Notifications\OperationalNotification;
use Illuminate\Database\Eloquent\Model;

class OperationalNotificationObserver
{
    public function created(Model $model): void
    {
        if ($model instanceof DriverAssignment) {
            $model->driver?->notify(new OperationalNotification(
                event: 'assignment.offered',
                title: 'Assignment baru',
                message: 'Kamu menerima penawaran assignment baru.',
                resourceType: 'driver_assignment',
                resourceId: $model->id,
                meta: ['booking_id' => $model->booking_id, 'vehicle_id' => $model->vehicle_id],
            ));
        }
    }

    public function updated(Model $model): void
    {
        if ($model instanceof Payment && $model->wasChanged('status')) {
            $model->customer?->notify(new OperationalNotification(
                event: 'payment.'.$model->status->value,
                title: 'Status pembayaran diperbarui',
                message: 'Pembayaran booking kamu berstatus '.$model->status->value.'.',
                resourceType: 'payment',
                resourceId: $model->id,
                meta: ['booking_id' => $model->booking_id, 'status' => $model->status->value, 'rejection_reason' => $model->rejection_reason],
            ));
        }

        if ($model instanceof Booking && $model->wasChanged('status')) {
            $model->customer?->notify(new OperationalNotification(
                event: 'booking.'.$model->status->value,
                title: 'Status booking diperbarui',
                message: 'Booking '.$model->booking_code.' berstatus '.$model->status->value.'.',
                resourceType: 'booking',
                resourceId: $model->id,
                meta: ['booking_code' => $model->booking_code, 'status' => $model->status->value],
            ));
        }

        if ($model instanceof DriverAssignment && $model->wasChanged('status')) {
            $model->offeredBy?->notify(new OperationalNotification(
                event: 'assignment.'.$model->status->value,
                title: 'Respons assignment',
                message: 'Driver merespons assignment dengan status '.$model->status->value.'.',
                resourceType: 'driver_assignment',
                resourceId: $model->id,
                meta: ['booking_id' => $model->booking_id, 'driver_id' => $model->driver_id, 'status' => $model->status->value],
            ));
        }

        if ($model instanceof DriverProfile && $model->wasChanged('verification_status')) {
            $model->user?->notify(new OperationalNotification(
                event: 'driver.verification.'.$model->verification_status->value,
                title: 'Verifikasi driver diperbarui',
                message: 'Status verifikasi akun driver kamu: '.$model->verification_status->value.'.',
                resourceType: 'driver_profile',
                resourceId: $model->id,
                meta: ['status' => $model->verification_status->value, 'rejection_reason' => $model->rejection_reason],
            ));
        }

        if ($model instanceof Vehicle && $model->wasChanged('verification_status')) {
            $model->driverProfile?->user?->notify(new OperationalNotification(
                event: 'vehicle.verification.'.$model->verification_status->value,
                title: 'Verifikasi kendaraan diperbarui',
                message: 'Status verifikasi kendaraan '.$model->plate_number.': '.$model->verification_status->value.'.',
                resourceType: 'vehicle',
                resourceId: $model->id,
                meta: ['status' => $model->verification_status->value, 'rejection_reason' => $model->rejection_reason],
            ));
        }

        if ($model instanceof Withdrawal && $model->wasChanged('status')) {
            $model->driverProfile?->user?->notify(new OperationalNotification(
                event: 'withdrawal.'.$model->status->value,
                title: 'Status withdrawal diperbarui',
                message: 'Withdrawal kamu berstatus '.$model->status->value.'.',
                resourceType: 'withdrawal',
                resourceId: $model->id,
                meta: ['status' => $model->status->value, 'points' => $model->points, 'amount' => $model->amount, 'rejection_reason' => $model->rejection_reason],
            ));
        }
    }
}
