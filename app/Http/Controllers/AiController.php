<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class AiController extends Controller
{
    public function aisale(){
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo', // yoki 'gpt-4o-mini'ni ishlatishingiz mumkin
                'messages' => [
                    ['role' => 'user', 'content' => 'Bugun qancha savdo bo\'ldi va qancha foyda keldik?'],
                ],
                'max_tokens' => 100,
            ]);
    
            return response()->json($response);
        } catch (\Exception $e) {
            // Xatolikni ushlash va javob qaytarish
            return response()->json([
                'error' => 'Xatolik yuz berdi: ' . $e->getMessage()
            ], 500);
        }
    }
}
