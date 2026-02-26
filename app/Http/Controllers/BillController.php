<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use NumberToWords\NumberToWords;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")
                    ->orWhere('total_amount', 'like', "%{$search}%")
                    // Search by formatted date (e.g. "21 January")
                    ->orWhereRaw("DATE_FORMAT(created_at, '%d %M %Y') LIKE ?", ["%{$search}%"]);
            });
        }

        $bills = $query
            ->orderBy('created_at', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Bills/Index', [
            'bills' => $bills,
            'search' => $search,
        ]);
    }

    public function show($id)
    {
        $bill = Bill::with(['subBills', 'documents.uploader'])->findOrFail($id);
        return Inertia::render('Bills/Show', [
            'bill' => $bill,
        ]);
    }

    public function create()
    {
        return Inertia::render('Bills/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client' => 'required|string|max:255',
            'total_amount' => 'required|numeric',
            'sub_bills' => 'required|array|min:1',
            'sub_bills.*.item' => 'required|string',
            'sub_bills.*.quantity' => 'required|integer|min:1',
            'sub_bills.*.unit_price' => 'required|numeric|min:0',
            'sub_bills.*.amount' => 'required|numeric|min:0',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240', // Max 10MB per file
        ]);

        $bill = Bill::create([
            'name' => $validated['name'],
            'client' => $validated['client'],
            'total_amount' => $validated['total_amount'],
        ]);

        foreach ($validated['sub_bills'] as $sub) {
            $bill->subBills()->create($sub);
        }

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $this->storeDocument($bill, $file);
            }
        }

        return redirect()->route('bills')->with('success', 'Bill created successfully.');
    }

    public function edit($id)
    {
        $bill = Bill::with(['subBills', 'documents.uploader'])->findOrFail($id);
        return Inertia::render('Bills/Edit', [
            'bill' => $bill,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client' => 'required|string|max:255',
            'total_amount' => 'required|numeric',
            'sub_bills' => 'required|array|min:1',
            'sub_bills.*.item' => 'required|string',
            'sub_bills.*.quantity' => 'required|integer|min:1',
            'sub_bills.*.unit_price' => 'required|numeric|min:0',
            'sub_bills.*.amount' => 'required|numeric|min:0',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240', // Max 10MB per file
        ]);

        $bill = Bill::findOrFail($id);
        $bill->update([
            'name' => $validated['name'],
            'client' => $validated['client'],
            'total_amount' => $validated['total_amount'],
        ]);

        $bill->subBills()->delete(); // Optional: clean up old sub-bills
        foreach ($validated['sub_bills'] as $sub) {
            $bill->subBills()->create($sub);
        }

        // Handle new document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $this->storeDocument($bill, $file);
            }
        }

        return redirect()->route('bills-edit', $id)->with('success', 'Bill updated successfully.');
    }

    public function destroy($id)
    {
        $bill = Bill::findOrFail($id);

        // Delete all documents and their files
        foreach ($bill->documents as $document) {
            Storage::disk('public')->delete($document->path);
            $document->delete();
        }

        // Delete related sub-bills
        $bill->subBills()->delete();

        // Delete the main bill
        $bill->delete();

        return redirect()->route('bills')->with('success', 'Bill deleted successfully.');
    }

    public function download($id)
    {
        $bill = Bill::with('subBills')->findOrFail($id);
        $issueDate = Carbon::now()->format('F j, Y');

        $numberToWords = new NumberToWords();
        $numberTransformer = $numberToWords->getNumberTransformer('en');
        $amountInWords = ucfirst($numberTransformer->toWords($bill->total_amount)) . ' Taka Only';

        $pdf = Pdf::loadView('pdf.bill', [
            'bill' => $bill,
            'issueDate' => $issueDate,
            'amountInWords' => $amountInWords,
        ]);

        return $pdf->download("bill-{$bill->id}-{$issueDate}.pdf");
    }

    /**
     * Store a document for a bill
     */
    private function storeDocument(Bill $bill, $file)
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('bill_documents', $filename, 'public');

        $bill->documents()->create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * Delete a specific document
     */
    public function deleteDocument($billId, $documentId)
    {
        $bill = Bill::findOrFail($billId);
        $document = $bill->documents()->findOrFail($documentId);

        // Delete the file from storage
        Storage::disk('public')->delete($document->path);

        // Delete the database record
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Download a specific document
     */
    public function downloadDocument($billId, $documentId)
    {
        $bill = Bill::findOrFail($billId);
        $document = $bill->documents()->findOrFail($documentId);

        $filePath = storage_path('app/public/' . $document->path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $document->filename);
    }
}
