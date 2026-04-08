<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\Notification;
use App\Models\User;
use App\Events\NotificationCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of support tickets.
     */
    public function index()
    {
        $tickets = SupportTicket::with('creator:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('SupportTickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Show the form for creating a new support ticket.
     */
    public function create()
    {
        return Inertia::render('SupportTickets/Create');
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'pending';
        $validated['priority'] = $validated['priority'] ?? 'medium';

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/support_tickets'), $imageName);
            $validated['image'] = 'uploads/support_tickets/' . $imageName;
        }

        $ticket = SupportTicket::create($validated);

        // Create notification for admins
        $this->notifyAdmins($ticket);

        return redirect()->route('support-tickets.index')->with('success', 'Support ticket created successfully!');
    }

    /**
     * Display the specified support ticket.
     */
    public function show(SupportTicket $ticket)
    {
        $ticket->load('creator:id,name,email');

        return Inertia::render('SupportTickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Update the status of the specified support ticket.
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,done',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update($validated);

        // Notify ticket creator about status change
        $this->notifyCreator($ticket, $oldStatus);

        return back()->with('success', 'Ticket status updated successfully!');
    }

    /**
     * Update the priority of the specified support ticket.
     */
    public function updatePriority(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket->update($validated);

        return back()->with('success', 'Ticket priority updated successfully!');
    }

    /**
     * Remove the specified support ticket from storage.
     */
    public function destroy(SupportTicket $ticket)
    {
        // Delete image if exists
        if ($ticket->image && file_exists(public_path($ticket->image))) {
            unlink(public_path($ticket->image));
        }

        $ticket->delete();

        return back()->with('success', 'Support ticket deleted successfully!');
    }

    /**
     * Notify all admin users about new ticket
     */
    private function notifyAdmins(SupportTicket $ticket)
    {
        // Get all admin users (adjust based on your role system)
        $admins = User::where('role', 'super_admin')
            ->get();

        foreach ($admins as $admin) {
            $notification = Notification::create([
                'user_id' => $admin->id,
                'type' => 'support_ticket_created',
                'title' => 'New Support Ticket',
                'message' => "New support ticket created by {$ticket->creator->name}: {$ticket->name}",
                'link' => route('support-tickets.show', $ticket->id),
                'actor_id' => $ticket->created_by,
                'is_read' => false,
            ]);

            broadcast(new NotificationCreated($notification))->toOthers();
        }
    }

    /**
     * Notify ticket creator about status change
     */
    private function notifyCreator(SupportTicket $ticket, $oldStatus)
    {
        if ($ticket->created_by === Auth::id()) {
            return; // Don't notify if the creator is updating their own ticket
        }

        $notification = Notification::create([
            'user_id' => $ticket->created_by,
            'type' => 'support_ticket_updated',
            'title' => 'Support Ticket Updated',
            'message' => "Your ticket \"{$ticket->name}\" status changed from {$oldStatus} to {$ticket->status}",
            'link' => route('support-tickets.show', $ticket->id),
            'actor_id' => Auth::id(),
            'is_read' => false,
        ]);

        broadcast(new NotificationCreated($notification))->toOthers();
    }
}
