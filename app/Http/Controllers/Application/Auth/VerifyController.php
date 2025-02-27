<?php

namespace App\Http\Controllers\Application\Auth;

use App\Http\Controllers\Controller;
use App\Models\Application\AppUsers;
use App\Models\VerificationOtp;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyController extends Controller
{
    private $whatsappClient;

    public function __construct()
    {
        $this->whatsappClient = new Client();
    }
    public function sendOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
        ]);

        $user = AppUsers::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User not found."
            ], 404);
        }

        $phone = $user->phone;

        // Delete any existing OTP for this user
        VerificationOtp::where('user_id', $user->id)->delete();

        // Generate new OTP
        $otp = rand(100000, 999999);
        $verificationOtp = VerificationOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        if ($verificationOtp) {
            try {
                // Clean and format phone number to include country code
                $whatsappNumber = preg_replace('/[^0-9]/', '', $phone);
                if (!str_starts_with($whatsappNumber, '92')) {
                    $whatsappNumber = '92' . ltrim($whatsappNumber, '0');
                }

                // WhatsApp Template Payload
                $payload = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('WHATSAPP_TOKEN'),
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'messaging_product' => 'whatsapp',
                        'to' => $whatsappNumber,
                        'type' => 'template',
                        'template' => [
                            'name' => 'verification_code_template', // Your Template Name
                            'language' => [
                                'code' => 'en_US'
                            ],
                            'components' => [
                                [
                                    'type' => 'body',
                                    'parameters' => [
                                        [
                                            'type' => 'text',
                                            'text' => $otp // Dynamic OTP
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'button',
                                    'sub_type' => 'url',
                                    'index' => '0',
                                    'parameters' => [
                                        [
                                            'type' => 'text',
                                            'text' => $otp
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'http_errors' => false
                ];

                // Send WhatsApp Template Message
                $response = $this->whatsappClient->post("https://graph.facebook.com/v17.0/515333351669022/messages", $payload);
                $responseBody = json_decode($response->getBody(), true);

                if ($response->getStatusCode() !== 200) {
                    throw new \Exception("WhatsApp API error: " . ($responseBody['error']['message'] ?? 'Unknown error'));
                }

                return response()->json([
                    "status" => "success",
                    "message" => "OTP sent successfully",
                    "phone" => $whatsappNumber
                ], 200);
            } catch (\Exception $e) {
                Log::error("Failed to send WhatsApp verification: " . $e->getMessage(), [
                    'user_id' => $user->id,
                    'phone' => $whatsappNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    "status" => "error",
                    "message" => "Failed to send OTP. Please try again.",
                    "error" => $e->getMessage()
                ], 500);
            }
        }
    }


    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|max:6',
        ]);

        $user = AppUsers::where('phone', $request->phone)->first();
        $otp = $request->otp;

        if (!$user) {
            return response()->json([
                'status' => "error",
                'message' => 'User not found'
            ], 404);
        }

        $verificationOtp = VerificationOtp::where('user_id', $user->id)->where('otp', $otp)->first();
        if ($verificationOtp) {
            $user->phone_verified_at = now();
            $user->save();

            return response()->json([
                'status' => "success",
                'message' => 'Phone number verified successfully'
            ], 200);
        }

        return response()->json([
            'status' => "error",
            'message' => 'Invalid OTP'
        ], 400);
    }
}
