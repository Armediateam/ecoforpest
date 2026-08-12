<?php

namespace App\Filament\Resources\ProposalCustomerResource\Pages;

use App\Filament\Resources\ProposalCustomerResource;
use App\Mail\ProposalMail;
use App\Traits\GridBuilderConverter;
use Filament\Actions;
use App\Livewire\Components\Support\ResourceTabsConfiguration;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProposalCustomer;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PhoneHelper;

class ViewProposalCustomer extends ViewRecord
{
    use GridBuilderConverter;
    
    protected static string $resource = ProposalCustomerResource::class;

    protected static string $view = 'filament.resources.proposal-customer-resource.pages.view-proposal-customer-tabs';

    public $newComment;

    protected $listeners = ['commentsUpdated' => '$refresh'];

    public function getTabsConfiguration(): array
    {
        $rawConfig = ResourceTabsConfiguration::forProposalCustomer()->toArray();
        $processedTabs = [];

        if (isset($rawConfig['tabs']) && is_array($rawConfig['tabs'])) {
            foreach ($rawConfig['tabs'] as $key => $tabConfig) {
                if (isset($tabConfig['visible']) && is_callable($tabConfig['visible'])) {
                    if (!call_user_func($tabConfig['visible'], $this->record)) {
                        continue;
                    }
                    unset($tabConfig['visible']);
                }
                $processedTabs[$key] = $tabConfig;
            }
        }
        $rawConfig['tabs'] = $processedTabs;
        return $rawConfig;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $resource = static::getResource();
        return $resource::infolist($infolist);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendEmail')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Send Proposal via Email')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    Forms\Components\TextInput::make('recipient_email')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(fn($record) => $record->email ?? '')
                        ->placeholder('Enter recipient email address'),
                    Forms\Components\Textarea::make('custom_message')
                        ->label('Custom Message')
                        ->placeholder('Add a personal message (optional)')
                        ->rows(4)
                        ->default(fn($record) => $record->email_text ?? ''),
                ])
                ->action(function (array $data, $record) {
                    try {
                        $customMessage = $data['custom_message'] ?? '';
                        $recipientEmail = $data['recipient_email'];

                        // Temporarily set email for the mail class
                        $originalEmail = $record->email;
                        $record->email = $recipientEmail;

                        Mail::to($recipientEmail)->send(new ProposalMail($record, $customMessage));

                        // Restore original email
                        $record->email = $originalEmail;

                        Notification::make()
                            ->title('Email Sent Successfully')
                            ->body("Proposal has been sent to {$recipientEmail}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Email Failed')
                            ->body('Failed to send email: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Send Proposal via Email')
                ->modalSubmitActionLabel('Send Email'),
            Actions\Action::make('downloadPdf')
                ->disableLabel()
                ->color('gray')
                ->tooltip('Download PDF Proposal')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function ($record) {
                    $customer = $record->customer;
                    $customerData = ($record->related == 'customer') ? $record->customer : $record->lead;

                    // Load relationships for processing
                    $record->load(['proposalTemplate', 'country', 'paymentTerm', 'proposalOrder']);

                    if ($record->proposalOrder) {
                        try {
                            $record->load('proposalOrder.proposalItems');
                        } catch (\Exception $e) {
                            // Handle if proposalItems doesn't exist
                        }
                    }

                    // Process template content with dynamic placeholders
                    $templateContent = $this->processTemplateContent($record, $customerData);

                    $data = [
                        'proposal' => $record,
                        'customer' => $customerData,
                        'company' => config('app.name'),
                        'date' => \Carbon\Carbon::parse($record->date)->format('d F Y'),
                        'content' => $templateContent,
                        'hasTemplate' => !empty($record->proposalTemplate?->content),
                    ];

                    // Choose the appropriate view based on whether template exists
                    $view = $data['hasTemplate'] ? 'proposals.template-preview' : 'proposals.preview';

                    $pdf = Pdf::loadView($view, $data)
                        ->setPaper('a4')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'arial',
                        ]);
                    $proposalNumber = $record->id ?? 'draft';
                    $fileName = "Proposal-{$proposalNumber}.pdf";

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, $fileName);
                }),
            Actions\Action::make('add_comment')
                ->visible(fn(Model $record) => $record->allow_comments)
                ->form([
                    RichEditor::make('content')
                        ->label('Comment')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'bulletList',
                            'italic',
                            'link',
                            'orderedList',
                        ]),
                ])
                ->action(function (array $data): void {
                    $comment = $this->record->comments()->create([
                        'user_id' => auth()->id(),
                        'content' => $data['content'],
                    ]);

                    // Log the comment activity directly
                    activity()
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'comment_id' => $comment->id,
                            'content' => $data['content'],
                            'type' => 'comment'
                        ])
                        ->log("{$comment->user->name} menambahkan komentar pada Proposal Customer {$this->record->subject}");

                    $this->dispatch('commentsUpdated');

                    $adminUsers = \App\Models\User::with('roles')
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'Admin Reguler');
                        })->get();
                    foreach ($adminUsers as $admin) {
                        Notification::make()
                            ->title('Proposal Commented')
                            ->body('The proposal commented by ' . auth()->user()->name)
                            ->success()
                            ->actions([
                                Action::make('view')
                                    ->label('View Proposal')
                                    ->url(route('filament.secret.resources.proposal-customers.view', ['record' => $this->record->id])),
                            ])
                            ->sendToDatabase($admin);
                    }
                })
                ->modalHeading('Add Comment')
                ->modalSubmitActionLabel('Post Comment'),

            Actions\Action::make('approve')
                ->label('Approve Proposal')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function ($record) {
                    if ($record->status == 'accepted') {
                        return false;
                    }

                    return true;
                })
                ->icon('heroicon-o-check-circle')
                ->action(function ($record) {
                    $proposal = ProposalCustomer::find($record->id);

                    $proposal->approved_at = today();
                    $proposal->status = 'accepted';
                    $proposal->approver = auth()->id();
                    $proposal->save();

                    $adminUsers = \App\Models\User::with('roles')
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'Admin Reguler');
                        })->get();
                    foreach ($adminUsers as $admin) {
                        Notification::make()
                            ->title('Proposal Approved')
                            ->body('The proposal ' . $record->customer?->name . ' has been approved.')
                            ->success()
                            ->send();

                        Notification::make()
                            ->title('Proposal Approved')
                            ->body('The proposal ' . $record->customer?->name . ' has been approved.')
                            ->success()
                            ->actions([
                                Action::make('view')
                                    ->label('View Proposal')
                                    ->url(route('filament.secret.resources.proposal-customers.view', ['record' => $record->id])),
                            ])
                            ->sendToDatabase($admin);
                    }

                    return redirect()->to('secret/proposal-customers');
                }),
            Actions\Action::make('reject')
                ->label('Reject Proposal')
                ->color('danger')
                ->requiresConfirmation()
                ->icon('heroicon-o-x-circle')
                ->visible(function ($record) {
                    if ($record->status == 'declined') {
                        return false;
                    }

                    return true;
                })
                ->action(function ($record) {
                    $proposal = ProposalCustomer::find($record->id);

                    $proposal->declined_at = today();
                    $proposal->status = 'declined';
                    $proposal->approver = auth()->id();
                    $proposal->save();

                    $adminUsers = \App\Models\User::with('roles')
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'Admin Reguler');
                        })->get();
                    foreach ($adminUsers as $admin) {
                        Notification::make()
                            ->title('Proposal Rejected')
                            ->body('Proposal for ' . $record->customer?->name . ' has been rejected.')
                            ->danger()
                            ->send();

                        Notification::make()
                            ->title('Proposal Rejected')
                            ->body('Proposal for ' . $record->customer?->name . ' has been rejected.')
                            ->danger()
                            ->actions([
                                Action::make('view')
                                    ->label('View Proposal')
                                    ->url(route('filament.secret.resources.proposal-customers.view', ['record' => $record->id])),
                            ])
                            ->sendToDatabase($admin);
                    }

                    return redirect()->to('secret/proposal-customers');
                }),
            Actions\EditAction::make(),
        ];
    }

    /**
     * Process template content and replace placeholders with actual data
     */
    private function processTemplateContent($proposal, $customer)
    {
        $content = $proposal->proposalTemplate?->content ?? '';

        if (empty($content)) {
            return '';
        }

        // First convert tiptap JSON to HTML
        $htmlContent = tiptap_converter()->asHTML($content);

        $order = $proposal->proposalOrder ? $proposal->proposalOrder->first() : null;        // Calculate totals
        $subtotal = $order?->subtotal ?? 0;
        $discountFixed = $order?->discount_fixed ?? 0;
        $discountPercent = $order?->discount_percent ?? 0;
        $adjustment = $order?->adjustment ?? 0;
        $total = $order?->total ?? 0;

        // Prepare replacement data
        $replacements = [
            '{customer}' => $customer?->name ?? 'N/A',
            '{customer_name}' => $customer?->name ?? 'N/A',
            '{customer_email}' => $customer?->email ?? $proposal->email ?? 'N/A',
            '{customer_phone}' => PhoneHelper::clean($customer?->phone) ?? PhoneHelper::clean($proposal->phone) ?? 'N/A',
            '{customer_address}' => $customer?->address ?? $proposal->address ?? 'N/A',
            '{company}' => config('app.name', 'Ecoforpest'),
            '{company_name}' => config('app.name', 'Ecoforpest'),
            '{date}' => \Carbon\Carbon::parse($proposal->date)->format('d F Y'),
            '{proposal_date}' => \Carbon\Carbon::parse($proposal->date)->format('d F Y'),
            '{valid_until}' => $proposal->open_till ? \Carbon\Carbon::parse($proposal->open_till)->format('d F Y') : 'N/A',
            '{proposal_number}' => $proposal->id ?? 'Draft',
            '{proposal_subject}' => $proposal->subject ?? 'N/A',
            '{subtotal}' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            '{discount_fixed}' => 'Rp ' . number_format($discountFixed, 0, ',', '.'),
            '{discount_percent}' => $discountPercent . '%',
            '{adjustment}' => 'Rp ' . number_format($adjustment, 0, ',', '.'),
            '{total}' => 'Rp ' . number_format($total, 0, ',', '.'),
            '{payment_term}' => $proposal->paymentTerm?->name ?? 'N/A',
            '{contract_start}' => $proposal->contract_start_date ? \Carbon\Carbon::parse($proposal->contract_start_date)->format('d F Y') : 'N/A',
            '{contract_end}' => $proposal->contract_end_date ? \Carbon\Carbon::parse($proposal->contract_end_date)->format('d F Y') : 'N/A',
            '{warranty_term}' => $proposal->warranty_term ?? 'N/A',
            '{warranty_type}' => $proposal->warranty_type ?? 'N/A',
            '{to}' => $proposal->to ?? 'N/A',
            '{email}' => $proposal->email ?? 'N/A',
            '{phone}' => $proposal->phone ?? 'N/A',
            '{address}' => $proposal->address ?? 'N/A',
            '{city}' => $proposal->city ?? 'N/A',
            '{state}' => $proposal->state ?? 'N/A',
            '{zip_code}' => $proposal->zip_code ?? 'N/A',
            '{country}' => $proposal->country?->name ?? 'N/A',
        ];

        // Replace placeholders in HTML content
        $processedContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $htmlContent
        );

        // Convert Filament TipTap grid builder to PDF-compatible table layout
        $processedContent = $this->convertGridBuilderToTable($processedContent);

        return $processedContent;
    }
}
