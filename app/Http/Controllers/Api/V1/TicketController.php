<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RestrictsTickets;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Störungen')]
class TicketController extends ApiController
{
    use RestrictsTickets;

    protected static string $ability = 'tickets';

    public const INCLUDES = ['property', 'asset', 'structureNode', 'reporter', 'assignee', 'company', 'contract', 'replies'];

    #[Endpoint('Störungen auflisten', 'Paginierte Liste aller Störungen, Mängel und Anfragen des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Titel und Beschreibung.', type: 'string')]
    #[QueryParameter('filter[ticket_number]', description: 'Störungsnummer (fortlaufend je Mandant), mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'Status: reported, accepted, in_progress, waiting, resolved, closed; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[priority]', description: 'Priorität: p1, p2, p3; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[type]', description: 'Art: fault, defect, request.', type: 'string')]
    #[QueryParameter('filter[category]', description: 'Kategorie: electrical, plumbing, hvac, refrigeration, building, safety, cleaning, other.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[asset_id]', description: 'Anlagen-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[structure_node_id]', description: 'Strukturknoten-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[assigned_to]', description: 'Benutzer-ID des Bearbeiters.', type: 'string')]
    #[QueryParameter('filter[company_id]', description: 'Firmen-ID des Servicepartners.', type: 'string')]
    #[QueryParameter('filter[reported_by]', description: 'Benutzer-ID des Melders.', type: 'string')]
    #[QueryParameter('filter[created_from]', description: 'Gemeldet ab Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[created_to]', description: 'Gemeldet bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[due_before]', description: 'Fällig bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: ticket_number, created_at, updated_at, due_at, priority, status, title.', type: 'string', default: '-created_at')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, asset, structureNode, reporter, assignee, company, contract, replies.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Ticket::class);

        $tickets = QueryBuilder::for($this->visibleTickets())
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%"))),
                AllowedFilter::exact('ticket_number'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category'),
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('asset_id'),
                AllowedFilter::exact('structure_node_id'),
                AllowedFilter::exact('assigned_to'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('reported_by'),
                AllowedFilter::callback('created_from', fn (Builder $query, string $value) => $query->whereDate('created_at', '>=', $value)),
                AllowedFilter::callback('created_to', fn (Builder $query, string $value) => $query->whereDate('created_at', '<=', $value)),
                AllowedFilter::callback('due_before', fn (Builder $query, string $value) => $query->whereDate('due_at', '<=', $value)),
            )
            ->allowedSorts('ticket_number', 'created_at', 'updated_at', 'due_at', 'priority', 'status', 'title')
            ->allowedIncludes(
                'property',
                'asset',
                'structureNode',
                'reporter',
                'assignee',
                'company',
                'contract',
                AllowedInclude::relationship('replies', 'replies.author'),
            )
            ->defaultSort('-created_at');

        return TicketResource::collection($this->paginate($tickets, $request));
    }

    #[Endpoint('Störung anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, asset, structureNode, reporter, assignee, company, contract, replies.', type: 'string')]
    public function show(Request $request, Ticket $ticket): TicketResource
    {
        $this->authorizeTicket('view', $ticket);

        $ticket->load($this->relationsToLoad($request));

        return new TicketResource($ticket);
    }

    #[Endpoint('Störung anlegen', 'Der Token-Benutzer wird als Melder eingetragen; SLA-Fristen werden aus der SLA-Definition der Priorität berechnet.')]
    public function store(StoreTicketRequest $request): TicketResource
    {
        $ticket = new Ticket($request->validated());
        $ticket->organization_id = $this->organizationId();
        $ticket->reported_by = $request->user()->getKey();
        $ticket->save();

        return new TicketResource($ticket->load(['property', 'asset']));
    }

    #[Endpoint('Störung ändern', 'Statuswechsel setzen die Zeitstempel (erledigt, pausiert) und Benachrichtigungen wie in der Oberfläche.')]
    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $this->authorizeTicket('update', $ticket);

        $data = $request->validated();

        if (ServicePartner::isExternal($this->apiUser())) {
            unset($data['company_id'], $data['contract_id']);
        }

        $ticket->fill($data)->save();

        return new TicketResource($ticket->load(['property', 'asset']));
    }

    #[Endpoint('Störung löschen', 'Löscht die Störung samt Antworten.')]
    public function destroy(Ticket $ticket): Response
    {
        $this->authorizeTicket('delete', $ticket);

        $ticket->delete();

        return response()->noContent();
    }

    /**
     * @return array<int, string>
     */
    private function relationsToLoad(Request $request): array
    {
        return array_map(
            fn (string $include): string => $include === 'replies' ? 'replies.author' : $include,
            $this->requestedIncludes($request, self::INCLUDES),
        );
    }
}
