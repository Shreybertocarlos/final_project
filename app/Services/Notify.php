<?php
namespace App\Services;


class Notify {

    // Created Notification
    static function createdNotification() {
        return notify()->success('Created Successfully', 'Success!');
    }

    // Updated Notification
    static function updatedNotification() {
        return notify()->success('Updated Successfully', 'Success!');
    }

    // Deleted Notification


    static function deletedNotification() {
        return notify()->success('Deleted Successfully', 'Success!');
    }

    static function errorNotification(string $error) {
        return notify()->success($error, 'Error!');
    }

    static function successNotification(string $message) {
        return notify()->success($message, 'Success!');
    }




}
