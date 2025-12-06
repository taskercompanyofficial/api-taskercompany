<?php

namespace App\Http\Controllers\Crm\Authenticated;

use App\Http\Controllers\Controller;
use App\Models\AssignedJobs;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Notifications;
use App\Models\Scedular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use App\Events\NewNotification;
use App\Models\StoreUserSpecific;

class ComplaintController extends Controller
{
    private $whatsappClient;

    public function __construct()
    {
        $this->whatsappClient = new Client();
    }

    public function index(Request $request)
    {
        try {
            $perPage = max(1, min(100, (int) $request->input('per_page', 50)));
            $page = max(1, (int) $request->input('page', 1));
            $q = trim($request->input('q', ''));
            $status = $request->input('status');
            $brand_id = $request->input('brand_id');
            $branch_id = $request->input('branch_id');
            $from = $request->input('from');
            $to = $request->input('to');

            // Decode filters and sorting inputs
            $filters = json_decode($request->input('filters', '[]'), true);
            $logic = strtolower($request->input('logic', 'AND'));
            $sort = json_decode($request->input('sort', '[]'), true);

            // Handle multiple statuses (dot-separated values) or apply default statuses
            $defaultStatuses = [
                'open',
                'objection',
                'hold-by-brand',
                'hold-by-customer',
                'assigned-to-technician',
                'part-demand',
                'service-lifting',
                'party-lifting',
                'unit-in-service-center',
                'reinstallation-pending',
                'kit-in-service-center',
                'part-in-service-center',
                'delivery-pending',
                'quotation-applied',
                'installation-pending',
                'in-progress',
                'delivered',
                'pending-by-brand',
                'feedback-pending',
                'completed',
                'cashback-dealer',
                'warrenty-slip-cash',
                'code-pending',
                'amount-pending',
                'closed',
                'cancelled',
            ];

            $statusesToFilter = [];
            if ($status) {
                $statusesToFilter = array_map('trim', explode('.', $status));
            } else {
                $statusesToFilter = $defaultStatuses;
            }

            $complaintsQuery = Complaint::query()
                ->with(['brand', 'branch'])
                ->select('complaints.*') // Ensures we get all columns
                ->addSelect([
                    'technician' => DB::table('staff')
                        ->select('full_name')
                        ->whereColumn('staff.id', 'complaints.technician')
                        ->limit(1)
                ])
                ->when($q, function ($query) use ($q) {
                    $query->where(function ($query) use ($q) {
                        $searchableFields = [
                            'user_id', 'complain_num', 'brand_complaint_no', 'applicant_name', 'applicant_email',
                            'applicant_phone', 'applicant_whatsapp', 'extra_numbers', 'reference_by', 'dealer',
                            'applicant_adress', 'description', 'branch_id', 'brand_id', 'product', 'model',
                            'serial_number_ind', 'serial_number_oud', 'mq_nmb', 'p_date', 'complete_date',
                            'amount', 'product_type', 'technician', 'status', 'working_details', 'complaint_type',
                            'provided_services', 'warranty_type', 'happy_call_remarks', 'call_status', 'priority',
                            'files', 'cancellation_reason', 'cancellation_details', 'cancellation_file'
                        ];

                        foreach ($searchableFields as $field) {
                            $query->orWhere($field, 'like', "%$q%");
                        }
                    });
                })
                ->whereIn('status', $statusesToFilter) // Apply determined statuses
                ->when($brand_id, fn($query) => $query->where('brand_id', $brand_id))
                ->when($branch_id, fn($query) => $query->where('branch_id', $branch_id))
                ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]));

            // Apply filters dynamically
            if (!empty($filters)) {
                $complaintsQuery->where(function ($query) use ($filters, $logic) {
                    foreach ($filters as $index => $filter) {
                        $method = ($index === 0) ? 'where' : ($logic === 'or' ? 'orWhere' : 'where');

                        if ($filter['condition'] === 'null') {
                            $query->{$method}($filter['id'], null);
                        } elseif ($filter['condition'] === 'between') {
                            $values = explode(',', $filter['value']);
                            if (count($values) === 2) {
                                $query->{$method . 'Between'}($filter['id'], [$values[0], $values[1]]);
                            }
                        } elseif (in_array($filter['condition'], ['in', 'not in'])) {
                            $values = array_map('trim', explode('.', $filter['value'])); // Split by dots instead of commas
                            $query->{$method . ($filter['condition'] === 'in' ? 'In' : 'NotIn')}($filter['id'], $values);
                        } else {
                            // Handle LIKE conditions
                            if (in_array($filter['condition'], ['like', 'not like'])) {
                                $value = "%{$filter['value']}%";
                            } else {
                                $value = $filter['value'];
                            }
                            $query->{$method}($filter['id'], $filter['condition'], $value);
                        }
                    }
                });
            }

            // Apply sorting
            if (!empty($sort)) {
                foreach ($sort as $sortItem) {
                    $direction = $sortItem['desc'] ? 'desc' : 'asc';
                    $complaintsQuery->orderBy($sortItem['id'], $direction);
                }
            } else {
                $complaintsQuery->orderByDesc('created_at');
            }

            // Pagination
            $complaints = $complaintsQuery->paginate($perPage, ['*'], 'page', $page);

            $complaintsData = $complaints->getCollection();

            return response()->json([
                'data' => $complaintsData,
                'pagination' => [
                    'current_page' => $complaints->currentPage(),
                    'last_page' => $complaints->lastPage(),
                    'first_page' => 1,
                    'per_page' => $complaints->perPage(),
                    'total' => $complaints->total(),
                    'next_page' => $complaints->hasMorePages() ? $complaints->currentPage() + 1 : null,
                    'prev_page' => $complaints->currentPage() > 1 ? $complaints->currentPage() - 1 : null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching complaints: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch complaints'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $payload = $request->validate([
                'brand_complaint_no' => 'nullable|string|max:255',
                'applicant_email' => 'nullable|email|max:255',
                'applicant_name' => 'required|string|max:255',
                'applicant_phone' => 'required|string|max:20',
                'applicant_whatsapp' => 'required|string|max:20',
                'applicant_adress' => 'required|string|max:500',
                'brand_id' => 'required|integer',
                'branch_id' => 'required|integer',
                'extra_numbers' => 'nullable|string|max:255',
                'reference_by' => 'nullable|string|max:255',
                'product' => 'nullable|string|max:255',
                'complaint_type' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'status' => 'required|string|max:50',
                'call_status' => 'nullable|string|max:50',
            ]);

            DB::beginTransaction();

            $payload['user_id'] = $request->user() ? $request->user()->id : 12;
            $lastComplaint = Complaint::latest('id')->first();
            $newId = $lastComplaint ? $lastComplaint->id + 1 : 1;
            $payload['complain_num'] = 'TC' . now()->format('dmY') . $newId;

            $complaint = Complaint::create($payload);
            $complaint->refresh();
            DB::commit();

            $title = "New Complaint";
            $message = "A New Complaint has been received!";
            $link = "https://taskercompany.com/crm/complaints/" . $complaint->id;
            $status = "info";

            event(new NewNotification($title, $message, $status, $link));

            // Send WhatsApp notification
            try {
                $this->sendWhatsAppNotification($payload, 'complaint_create_template', $payload['applicant_whatsapp']);
            } catch (\Exception $e) {
                Log::error("WhatsApp notification failed: " . $e->getMessage());
                // Continue execution even if WhatsApp notification fails
            }

            return response()->json([
                "status" => "success",
                "message" => "Complaint has been created successfully",
                "data" => $complaint->fresh(), // Get fresh instance with all attributes
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error",
                "message" => "Validation failed",
                "errors" => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating Complaint: " . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                "status" => "error",
                "message" => "Failed to create complaint" . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $complaint = Complaint::with(['brand', 'branch'])->find($id);

            if (!$complaint) {
                $complaint = Complaint::with(['brand', 'branch'])->where('complain_num', $id)->first();
            }

            if (!$complaint) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Complaint not found'
                ], 404);
            }

            return response()->json($complaint);
        } catch (\Exception $e) {
            Log::error("Error fetching complaint: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch complaint due to an unexpected error.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $complaint = Complaint::findOrFail($id);
            $oldStatus = $complaint->status;

            $payload = $request->validate([
                'brand_complaint_no' => 'nullable|string|max:255',
                'applicant_name' => 'required|string|max:255',
                'applicant_email' => 'nullable|email|max:255',
                'applicant_phone' => 'required|string|max:20',
                'applicant_whatsapp' => 'required|string|max:20',
                'applicant_adress' => 'required|string|max:500',
                'extra_numbers' => 'nullable|string|max:255',
                'reference_by' => 'nullable|string|max:255',
                'extra' => 'nullable|string',
                'description' => 'nullable|string',
                'branch_id' => 'nullable|integer|exists:branches,id',
                'brand_id' => 'nullable|integer',
                'product' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'working_details' => 'nullable|string',
                'serial_number_ind' => 'nullable|string|max:255',
                'serial_number_oud' => 'nullable|string|max:255',
                'mq_nmb' => 'nullable|string|max:255',
                'p_date' => 'nullable|date',
                'complete_date' => 'nullable|date',
                'amount' => 'nullable|numeric',
                'product_type' => 'nullable|string|max:255',
                'technician' => 'nullable',
                'status' => 'required|string|max:50',
                'complaint_type' => 'nullable|string|max:255',
                'provided_services' => 'nullable|string',
                'warranty_type' => 'nullable|string|max:255',
                'comments_for_technician' => 'nullable|string',
                'files' => 'nullable',
                'send_message_to_technician' => 'nullable|boolean',
                'call_status' => 'nullable|string|max:50',
            ]);

            $technicianChanged = $complaint->technician !== $payload['technician'];
            $oldData = $complaint->toArray();

            $complaint->update($payload);

            if ($request->user()->id == $payload['technician']) {
                $title = $request->user()->full_name . " has updated a complaint";
                $message = $request->user()->full_name . " has updated a complaint ID" . $complaint->complain_num;
                $status = "info";
                $link = "https://taskercompany.com/crm/complaints/" . $complaint->id;
                event(new NewNotification($title, $message, $status, $link));
            }
            $newData = $complaint->toArray();

            // Generate description by comparing changes
            $changes = [];
            foreach ($newData as $key => $value) {
                if ($key !== 'updated_at' && $key !== 'files' && isset($oldData[$key]) && $oldData[$key] !== $value) {
                    $changes[] = ucfirst(str_replace('_', ' ', $key)) . " changed from '{$oldData[$key]}' to '{$value}'";
                }
            }

            $description = empty($changes)
                ? 'Complaint updated with no field changes'
                : 'Complaint updated: ' . implode(', ', $changes);

            ComplaintHistory::create([
                'complaint_id' => $complaint->id,
                'user_id' => $request->user()->id,
                'data' => json_encode($complaint),
                'description' => $description
            ]);

            // Handle technician assignment and notification
            if (!empty($payload['send_message_to_technician'])) {
                $this->handleJobAssignment($complaint, $payload, $technicianChanged, $payload['technician']);
            }

            // Send WhatsApp message if status changed to closed
            if (in_array($payload['status'], ['closed', 'cancelled']) && $oldStatus !== $payload['status']) {
                $message = "Dear *{$complaint->applicant_name}*,\n\n";

                if ($payload['status'] === 'closed') {
                    $message .= "Apki shikayat (ID: {$complaint->complain_num}) ka masla hal kar diya gaya hai.\n\n";
                    $message .= "*Shukriya Tasker Company ka intekhab karne ka.*\n\n";
                    $message .= "Barah-e-karam mazeed maloomat ya madad ke liye neeche diye gaye raabta zaraye istemal karein:\n\n";
                    $message .= "- *Helpline:* 03025117000\n";
                    $message .= "- *Website:* www.taskercompany.com\n\n";
                    $message .= "*Important Note:* Tasker Company ke Technician ya kisi bhi doosre worker se direct rabta na karein. Agar aap aisa karte hain to kisi bhi nuqsan ya maslay ki zimmedari Tasker Company par nahi hogi.";
                } elseif ($payload['status'] === 'cancelled') {
                    $message .= "Apki shikayat (ID: {$complaint->complain_num}) cancel kar di gayi hai.\n\n";
                    $message .= "*Agar aap ko kisi qisam ka masla ho ya madad darkar ho to barah-e-karam neeche diye gaye raabta zaraye istemal karein:*\n\n";
                    $message .= "- *Helpline:* 03025117000\n";
                    $message .= "- *Website:* www.taskercompany.com\n\n";
                    $message .= "*Important Note:* Tasker Company ke Technician ya kisi bhi doosre worker se direct rabta na karein. Agar aap aisa karte hain to kisi bhi nuqsan ya maslay ki zimmedari Tasker Company par nahi hogi.";
                }

                $message .= "\n\nBest regards,\nTasker Company";

                try {
                    $this->sendWhatsAppTextMessage($complaint->applicant_whatsapp, $message);
                } catch (\Exception $e) {
                    Log::error("WhatsApp status update message failed: " . $e->getMessage());
                }
            }


            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Complaint has been updated successfully',
                'data' => $complaint
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating complaint: " . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update complaint: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $complaint = Complaint::findOrFail($id);

            // Delete all associated complaint histories first
            $complaintHistories = ComplaintHistory::where('complaint_id', $complaint->id);
            if ($complaintHistories->exists()) {
                $complaintHistories->delete();
            }

            // Delete all associated assigned jobs first
            $assignedJobs = AssignedJobs::where('job_id', $complaint->id);
            if ($assignedJobs->exists()) {
                $assignedJobs->delete();
            }

            // Now delete the complaint
            $complaint->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Complaint and its history have been deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting complaint: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete complaint: ' . $e->getMessage()
            ], 500);
        }
    }
    public function cancleComplaint($id, Request $request)
    {
        try {
            $payload = $request->validate([
                'reason' => 'required|string',
                'details' => 'required|string',
                'file' => 'nullable|file'
            ]);

            $complaint = Complaint::findOrFail($id);
            $complaint->status = 'cancelled';
            $complaint->cancellation_reason = $payload['reason'];
            $complaint->cancellation_details = $payload['details'];

            if ($request->hasFile('file')) {
                // Handle file upload if needed
                $file = $request->file('file');
                $path = $file->store('cancelled_complaints', 'public');
                $complaint->cancellation_file = $path;
            }

            $complaint->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Complaint has been cancelled successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error cancelling complaint: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel complaint'
            ], 500);
        }
    }
    public function scedualeComplaint(Request $request)
    {
        try {
            $payload = $request->validate([
                'complaint_id' => 'required|integer|exists:complaints,id',
                'schedule_date' => 'required|date|after:now',
                'schedule_time' => 'required|date_format:H:i',
                'remarks' => 'nullable|string|max:500'
            ]);

            $complaint = Complaint::findOrFail($payload['complaint_id']);

            // Update complaint status to scheduled
            $complaint->status = 'scheduled';
            $complaint->save();
            $sceduale = Scedular::create($payload);
            return response()->json([
                'status' => 'success',
                'message' => 'Complaint has been scheduled successfully',
                'data' => $complaint
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error scheduling complaint: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to schedule complaint'
            ], 500);
        }
    }
    public function technicianReachedOnSite($id)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->status = 'technician_reached';
        $complaint->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Complaint status updated to technician_reached'
        ]);
    }
    public function getComplaintHistory($id)
    {
        $complaintHistory = ComplaintHistory::where('complaint_id', $id)->with('user')->get();
        return response()->json($complaintHistory);
    }

    public function sendMessage(Request $request, $to)
    {
        try {
            $payload = $request->validate([
                'message_type' => 'required|string',
                'complain_num' => 'required|string',
                'applicant_name' => 'required|string',
                'applicant_phone' => 'required|string',
                'applicant_adress' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'nullable|string',
                'remarks' => 'nullable|string',
            ]);

            $templateName = $payload['message_type'];

            $this->sendWhatsAppNotification($request, $templateName, $to);

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error sending message: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    private function sendWhatsAppNotification($complaint, $templateName, $to)
    {
        try {
            // Clean and format the WhatsApp number
            $whatsappNumber = preg_replace('/[^0-9]/', '', $to);

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
                        'name' => $templateName,
                        'language' => ['code' => 'en_US'],
                        'components' => $this->getTemplateComponents($complaint, $templateName)
                    ]
                ]
            ];

            $response = $this->whatsappClient->post('https://graph.facebook.com/v21.0/501488956390575/messages', $payload);

            Log::info("WhatsApp notification sent successfully", [
                'template' => $templateName,
                'whatsapp_number' => $whatsappNumber
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp message: " . $e->getMessage(), [
                'template' => $templateName,
                'whatsapp_number' => $complaint->applicant_whatsapp ?? 'not_provided'
            ]);
            throw $e;
        }
    }

    private function sendWhatsAppTextMessage($to, $message)
    {
        try {
            $whatsappNumber = preg_replace('/[^0-9]/', '', $to);

            $payload = [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('WHATSAPP_TOKEN'),
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $whatsappNumber,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]
            ];

            $response = $this->whatsappClient->post('https://graph.facebook.com/v21.0/501488956390575/messages', $payload);

            Log::info("WhatsApp text message sent successfully", [
                'whatsapp_number' => $whatsappNumber
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp text message: " . $e->getMessage(), [
                'whatsapp_number' => $to
            ]);
            throw $e;
        }
    }

    private function getTemplateComponents($complaint, string $templateName)
    {
        $components = [
            [
                'type' => 'header',
                'parameters' => [
                    ['type' => 'text', 'text' => $complaint['applicant_name']]
                ]
            ]
        ];

        if ($templateName === 'complaint_create_template') {
            $components[] = [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $complaint['complain_num']],
                    ['type' => 'text', 'text' => $complaint['applicant_phone']],
                    ['type' => 'text', 'text' => $complaint['applicant_adress']],
                    ['type' => 'text', 'text' => $complaint['description']]
                ]
            ];

            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => 0,
                'parameters' => [
                    ['type' => 'text', 'text' => "https://www.taskercompany.com"]
                ]
            ];

            $components[] = [
                'type' => 'button',
                'sub_type' => 'VOICE_CALL',
                'index' => 1,
                'parameters' => [
                    ['type' => 'text', 'text' => $complaint['applicant_phone']]
                ]
            ];
        } else if ($templateName === 'auto_pay_reminder_2') {
            $components[] = [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $complaint['complain_num']],
                    ['type' => 'text', 'text' => $complaint['status']],
                    ['type' => 'text', 'text' => $complaint['remarks'] ?? 'No remarks']
                ]
            ];

            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => 0,
                'parameters' => [
                    ['type' => 'text', 'text' => "https://www.taskercompany.com"]
                ]
            ];
        }

        return $components;
    }

    private function handleJobAssignment($complaint, array $payload, bool $technicianChanged, $user_id)
    {
        // Check if job already exists
        $existingJob = AssignedJobs::where('job_id', $complaint->id)
            ->whereIn('status', ['open', 'pending'])
            ->first();

        if (!$existingJob) {
            // Create new job assignment only if none exists
            $job = AssignedJobs::create([
                'job_id' => $complaint->id,
                'assigned_by' => $user_id,
                'assigned_to' => $payload['technician'],
                'branch_id' => $payload['branch_id'],
                'description' => $payload['comments_for_technician'],
                'status' => 'pending',
            ]);
        } else {
            $job = $existingJob;
        }

        // Send notification if requested
        if ($job && ($payload['send_message_to_technician'] ?? false)) {
            $this->createAndSendNotification($complaint, $job, $technicianChanged, $payload['technician'], $user_id);
        }
    }

    private function createAndSendNotification($complaint, AssignedJobs $job, bool $technicianChanged, string $technicianId, $user_id)
    {
        $notificationTitle = $technicianChanged ? 'New Job Assigned' : 'Job Updated';
        $notificationBody = sprintf(
            "Complaint #%s\nCustomer: %s\nProduct: %s\nDescription: %s\n%s",
            $complaint->complain_num,
            $complaint->applicant_name,
            $complaint->product,
            $job->description,
            $technicianChanged ? "New assignment" : "Updated job"
        );

        $notification = Notifications::create([
            'user_id' => $user_id,
            'title' => $notificationTitle,
            'body' => $notificationBody,
            'type' => 'complaint_update',
            'params' => json_encode([
                'page' => 'complaint',
                'id' => $job->id,
            ], JSON_PRETTY_PRINT)
        ]);

        if ($notification) {
            $this->sendPushNotification($technicianId, $notificationTitle, $notificationBody);
        }
    }

    private function sendPushNotification(string $technicianId, string $title, string $body)
    {
        try {
            $pushToken = StoreUserSpecific::where('user_id', $technicianId)->first()->push_token;

            $response = $this->whatsappClient->post('https://exp.host/--/api/v2/push/send', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => $pushToken,
                    'title' => $title,
                    'body' => $body
                ]
            ]);

            Log::info("Push notification sent successfully", [
                'technician_id' => $technicianId,
                'title' => $title
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error("Error sending push notification: " . $e->getMessage(), [
                'technician_id' => $technicianId,
                'title' => $title
            ]);
        }
    }
}
