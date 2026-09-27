<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = JournalEntry::with('company', 'creator', 'lines.account');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->has('date_from')) {
            $query->where('entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('entry_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($entries);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|string',
            'entry_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,posted,reversed',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounting_accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        // Validate that debits equal credits
        $totalDebit = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            return $this->error('Debits must equal credits', 400);
        }

        return DB::transaction(function () use ($data) {
            $entry = JournalEntry::create([
                'company_id' => $data['company_id'],
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            foreach ($data['lines'] as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $this->success($entry->load('lines.account'), 'Journal entry created successfully', 201);
        });
    }

    public function show(JournalEntry $journalEntry)
    {
        return $this->success($journalEntry->load(['company', 'creator', 'lines.account']));
    }

    public function post(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return $this->error('Journal entry is not in draft status', 400);
        }

        $journalEntry->update(['status' => 'posted']);

        return $this->success($journalEntry->load('lines.account'), 'Journal entry posted successfully');
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'posted') {
            return $this->error('Journal entry must be posted to reverse', 400);
        }

        return DB::transaction(function () use ($journalEntry) {
            $reversedEntry = JournalEntry::create([
                'company_id' => $journalEntry->company_id,
                'reference_type' => 'reversal',
                'reference_id' => $journalEntry->id,
                'entry_date' => now(),
                'description' => 'Reversal of '.$journalEntry->description,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($journalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversedEntry->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            $journalEntry->update(['status' => 'reversed']);

            return $this->success($reversedEntry->load('lines.account'), 'Journal entry reversed successfully');
        });
    }
}
