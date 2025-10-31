<?php

namespace App\Enums;

enum DownloadStatus: string
{
    case PENDING = 'pending';
    case VERIFYING_URL = 'verifying_url';
    case URL_VERIFIED = 'url_verified';
    case URL_REJECTED = 'url_rejected';
    case DOWNLOADING = 'downloading';
    case DOWNLOAD_COMPLETED = 'download_completed';
    case SCANNING_FILE = 'scanning_file';
    case FILE_VERIFIED = 'file_verified';
    case FILE_REJECTED = 'file_rejected';
    case MOVING_TO_STORAGE = 'moving_to_storage';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::VERIFYING_URL => 'Verificando URL',
            self::URL_VERIFIED => 'URL Verificada',
            self::URL_REJECTED => 'URL Rechazada',
            self::DOWNLOADING => 'Descargando',
            self::DOWNLOAD_COMPLETED => 'Descarga Completada',
            self::SCANNING_FILE => 'Escaneando Archivo',
            self::FILE_VERIFIED => 'Archivo Verificado',
            self::FILE_REJECTED => 'Archivo Rechazado',
            self::MOVING_TO_STORAGE => 'Moviendo a Almacenamiento',
            self::COMPLETED => 'Completado',
            self::FAILED => 'Fallido',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Get color class for UI (Tailwind CSS)
     */
    public function colorClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
            self::VERIFYING_URL => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::URL_VERIFIED => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::URL_REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::DOWNLOADING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::DOWNLOAD_COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::SCANNING_FILE => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::FILE_VERIFIED => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::FILE_REJECTED => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::MOVING_TO_STORAGE => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
            self::COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::FAILED => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            self::CANCELLED => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    /**
     * Get progress percentage (0-100)
     */
    public function progress(): int
    {
        return match($this) {
            self::PENDING => 0,
            self::VERIFYING_URL => 10,
            self::URL_VERIFIED => 20,
            self::URL_REJECTED => 0, // Failed state
            self::DOWNLOADING => 40,
            self::DOWNLOAD_COMPLETED => 50,
            self::SCANNING_FILE => 70,
            self::FILE_VERIFIED => 80,
            self::FILE_REJECTED => 0, // Failed state
            self::MOVING_TO_STORAGE => 90,
            self::COMPLETED => 100,
            self::FAILED => 0,
            self::CANCELLED => 0,
        };
    }

    /**
     * Check if status is a terminal state (can't transition from here)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::URL_REJECTED,
            self::FILE_REJECTED,
        ]);
    }

    /**
     * Check if status indicates an error state
     */
    public function isError(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::URL_REJECTED,
            self::FILE_REJECTED,
        ]);
    }
}

