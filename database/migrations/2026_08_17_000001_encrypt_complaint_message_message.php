<?php

use App\Models\ComplaintMessage;
use App\Services\AesEncryptionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypt existing plaintext chat messages and switch the table to
     * store the ciphertext in `message_encrypted`.
     */
    public function up(): void
    {
        Schema::table('complaint_messages', function (Blueprint $table) {
            $table->text('message_encrypted')->nullable();
        });

        $encryption = App::make(AesEncryptionService::class);

        ComplaintMessage::query()
            ->whereNotNull('message')
            ->orderBy('id')
            ->each(function (ComplaintMessage $message) use ($encryption) {
                $message->forceFill([
                    'message_encrypted' => $encryption->encrypt((string) $message->message),
                ])->save();
            });

        Schema::table('complaint_messages', function (Blueprint $table) {
            $table->dropColumn('message');
            $table->text('message_encrypted')->nullable(false)->change();
        });
    }

    /**
     * Decrypt the messages back into a plaintext `message` column.
     */
    public function down(): void
    {
        Schema::table('complaint_messages', function (Blueprint $table) {
            $table->text('message')->nullable();
        });

        $encryption = App::make(AesEncryptionService::class);

        ComplaintMessage::query()
            ->orderBy('id')
            ->each(function (ComplaintMessage $message) use ($encryption) {
                $message->forceFill([
                    'message' => $encryption->decrypt((string) $message->message_encrypted) ?? '',
                ])->save();
            });

        Schema::table('complaint_messages', function (Blueprint $table) {
            $table->dropColumn('message_encrypted');
        });
    }
};
