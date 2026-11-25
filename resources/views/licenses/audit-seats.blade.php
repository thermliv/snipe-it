@extends('layouts/default')

@section('title')
    {{ trans('admin/licenses/general.seat_audit.title') }} - {{ $license->name }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">
                {{ trans('admin/licenses/general.seat_audit.title') }}: {{ $license->name }}
            </h1>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('admin/licenses/general.pool_audit.title') }}</h2>
                </div>
                <div class="box-body">
                    <p>{{ trans('admin/licenses/general.pool_audit.help') }}</p>
                    <p>{{ trans('admin/licenses/general.pool_audit.recorded_seats') }}: <strong>{{ $license->seats }}</strong></p>
                    @if ($latestPoolAudit)
                        <p>
                            @if ($latestPoolAudit->pool_audit_revision !== $license->pool_audit_revision)
                                {{ trans('admin/licenses/general.pool_audit.stale') }}
                            @elseif ((int) $latestPoolAudit->verified_seats !== (int) $latestPoolAudit->recorded_seats)
                                {{ trans('admin/licenses/general.pool_audit.discrepancy') }}
                            @else
                                {{ trans('admin/licenses/general.pool_audit.verified') }}
                            @endif
                            ({{ $latestPoolAudit->created_at->format('Y-m-d H:i') }},
                            {{ $latestPoolAudit->auditor?->display_name ?? trans('admin/reports/general.deleted_user') }};
                            {{ trans('admin/licenses/general.pool_audit.verified_seats') }}: {{ $latestPoolAudit->verified_seats }})
                        </p>
                    @else
                        <p>{{ trans('admin/licenses/general.pool_audit.not_audited') }}</p>
                    @endif
                    @foreach (['pool_audit_revision', 'verified_seats', 'notes'] as $field)
                        @foreach ($errors->get($field) as $message)
                            <p class="text-danger" role="alert">{{ $message }}</p>
                        @endforeach
                    @endforeach
                    <form method="post" action="{{ route('licenses.pool-audits.store', $license) }}" class="form-inline">
                        @csrf
                        <input type="hidden" name="pool_audit_revision" value="{{ $license->pool_audit_revision }}">
                        <div class="form-group">
                            <label for="verified-seats">{{ trans('admin/licenses/general.pool_audit.verified_seats') }}</label>
                            <input id="verified-seats" type="number" name="verified_seats" class="form-control" min="0" max="2147483647" value="{{ old('verified_seats', $license->seats) }}" required>
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="pool-audit-notes">{{ trans('admin/licenses/general.seat_audit.notes') }}</label>
                            <textarea id="pool-audit-notes" name="notes" class="form-control" rows="1" placeholder="{{ trans('admin/licenses/general.seat_audit.notes') }}">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ trans('admin/licenses/general.seat_audit.record') }}</button>
                    </form>
                </div>
            </div>

            @if ($seats->isEmpty())
                <p>{{ trans('admin/licenses/general.seat_audit.empty') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/licenses/general.seat') }}</th>
                                <th>{{ trans('admin/licenses/general.user') }}</th>
                                <th>{{ trans('admin/licenses/general.seat_audit.audit_result') }}</th>
                                <th>{{ trans('admin/licenses/general.seat_audit.new_audit') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($seats as $seat)
                                @php($seatAudits = $auditsBySeat->get($seat->id, collect()))
                                <tr>
                                    <td>{{ trans('admin/licenses/general.seat_count', ['count' => $seat->id]) }}</td>
                                    <td>{{ $seat->user?->display_name ?? trans('admin/reports/general.deleted_user') }}</td>
                                    <td>
                                        @if ($seatAudits->isNotEmpty())
                                            @foreach ($seatAudits as $audit)
                                                <div class="well well-sm">
                                                    <strong>{{ $audit->created_at->format('Y-m-d H:i') }}</strong><br>
                                                    {{ trans('admin/licenses/general.seat_audit.active_use') }}:
                                                    {{ $audit->in_use ? trans('general.yes') : trans('general.no') }}<br>
                                                    {{ trans('admin/licenses/general.user') }}:
                                                    {{ $audit->assigned_to_name ?? trans('admin/reports/general.deleted_user') }}<br>
                                                    {{ $audit->user_confirmed
                                                        ? trans('admin/licenses/general.seat_audit.confirmed')
                                                        : trans('admin/licenses/general.seat_audit.not_confirmed') }}<br>
                                                    {{ trans('general.audited_by') }}:
                                                    {{ $audit->auditor?->display_name ?? trans('admin/reports/general.deleted_user') }}
                                                    @if ($audit->notes)
                                                        <p>{{ $audit->notes }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            {{ trans('admin/licenses/general.seat_audit.not_audited') }}
                                        @endif
                                    </td>
                                    <td>
                                        <form method="post" action="{{ route('licenses.seat-audits.store', [$license, $seat]) }}" class="form-inline">
                                            @csrf
                                            <div class="form-group">
                                                <label for="in-use-{{ $seat->id }}">{{ trans('admin/licenses/general.seat_audit.active_use') }}</label>
                                                <select id="in-use-{{ $seat->id }}" name="in_use" class="form-control" required>
                                                    <option value="">{{ trans('general.select') }}</option>
                                                    <option value="1">{{ trans('general.yes') }}</option>
                                                    <option value="0">{{ trans('general.no') }}</option>
                                                </select>
                                            </div>
                                            <div class="checkbox">
                                                <label for="user-confirmed-{{ $seat->id }}">
                                                    <input type="hidden" name="user_confirmed" value="0">
                                                    <input id="user-confirmed-{{ $seat->id }}" type="checkbox" name="user_confirmed" value="1">
                                                    {{ trans('admin/licenses/general.seat_audit.user_confirmed') }}
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="sr-only" for="audit-notes-{{ $seat->id }}">{{ trans('admin/licenses/general.seat_audit.notes') }}</label>
                                                <textarea id="audit-notes-{{ $seat->id }}" name="notes" class="form-control" rows="1" placeholder="{{ trans('admin/licenses/general.seat_audit.notes') }}"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                {{ trans('admin/licenses/general.seat_audit.record') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $seats->links() }}
            @endif
        </div>
    </div>
@endsection
