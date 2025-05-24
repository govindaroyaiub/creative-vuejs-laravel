<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
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
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('client', 'like', "%{$search}%");
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
        $bill = Bill::with('subBills')->findOrFail($id);
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
        ]);

        $bill = Bill::create([
            'name' => $validated['name'],
            'client' => $validated['client'],
            'total_amount' => $validated['total_amount'],
        ]);

        foreach ($validated['sub_bills'] as $sub) {
            $bill->subBills()->create($sub);
        }

        return redirect()->route('bills')->with('success', 'Bill created successfully.');
    }

    public function edit($id)
    {
        $bill = Bill::with('subBills')->findOrFail($id);
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

        return redirect()->route('bills-edit', $id)->with('success', 'Bill updated successfully.');
    }

    public function destroy($id)
    {
        $bill = Bill::findOrFail($id);

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

        return $pdf->download("bill-{$bill->id}.pdf");
    }
}
