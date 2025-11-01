<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateDomainVirusTotalInfoJob;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    /**
     * Display a listing of domains.
     */
    public function index(Request $request)
    {
        $query = Domain::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by is_active
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by is_verified
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $domains = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($domains);
    }

    /**
     * Store a newly created domain.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:domains,name',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        // Create domain with pending VirusTotal status
        $domain = Domain::create(array_merge($validated, [
            'virustotal_status' => 'pending',
        ]));

        // Dispatch job to update VirusTotal information asynchronously
        UpdateDomainVirusTotalInfoJob::dispatch($domain->id);

        return response()->json($domain, 201);
    }

    /**
     * Display the specified domain.
     */
    public function show(Domain $domain)
    {
        return response()->json($domain);
    }

    /**
     * Update the specified domain.
     */
    public function update(Request $request, Domain $domain)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:domains,name,' . $domain->id,
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:2048',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ]);

        $domain->update($validated);

        return response()->json($domain);
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(Domain $domain)
    {
        $domain->delete();

        return response()->json(['message' => 'Domain deleted successfully']);
    }

    /**
     * Get VirusTotal information for a domain
     * Returns stored VirusTotal information (does not make new API call)
     */
    public function getVirusTotalInfo(Domain $domain): JsonResponse
    {
        return response()->json([
            'virustotal_reputation' => $domain->virustotal_reputation,
            'virustotal_votes_harmless' => $domain->virustotal_votes_harmless,
            'virustotal_votes_malicious' => $domain->virustotal_votes_malicious,
            'virustotal_last_analysis_date' => $domain->virustotal_last_analysis_date,
            'virustotal_last_analysis_stats' => $domain->virustotal_last_analysis_stats,
            'virustotal_categories' => $domain->virustotal_categories,
            'virustotal_whois' => $domain->virustotal_whois,
            'virustotal_subdomains' => $domain->virustotal_subdomains,
            'virustotal_last_checked_at' => $domain->virustotal_last_checked_at,
            'virustotal_status' => $domain->virustotal_status,
        ]);
    }

    /**
     * Refresh VirusTotal information for a domain
     * Dispatches a job to update the information asynchronously
     */
    public function refreshVirusTotalInfo(Domain $domain): JsonResponse
    {
        // Update status to indicate refresh is in progress
        $domain->update([
            'virustotal_status' => 'pending',
        ]);

        // Dispatch job to update VirusTotal information
        UpdateDomainVirusTotalInfoJob::dispatch($domain->id);

        return response()->json([
            'message' => 'VirusTotal information refresh has been queued',
            'domain_id' => $domain->id,
            'status' => 'pending',
        ]);
    }
}

