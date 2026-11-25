@extends('layouts/default')

@section('title')
    {{ trans('admin/licenses/general.pool_audit.report') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('admin/licenses/general.pool_audit.pool_history') }}</h2>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ trans('general.date') }}</th>
                                <th>{{ trans('general.license') }}</th>
                                <th>{{ trans('general.audited_by') }}</th>
                                <th>{{ trans('admin/licenses/general.pool_audit.recorded_seats') }}</th>
                                <th>{{ trans('admin/licenses/general.pool_audit.verified_seats') }}</th>
                                <th>{{ trans('general.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($poolAudits as $audit)
                                <tr>
                                    <td>{{ $audit->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $audit->license->name }}</td>
                                    <td>{{ $audit->auditor?->display_name ?? trans('admin/reports/general.deleted_user') }}</td>
                                    <td>{{ $audit->recorded_seats }}</td>
                                    <td>{{ $audit->verified_seats }}</td>
                                    <td>{{ $audit->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">{{ trans('admin/licenses/general.pool_audit.no_audits') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $poolAudits->links() }}
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('admin/licenses/general.pool_audit.seat_history') }}</h2>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ trans('general.date') }}</th>
                                <th>{{ trans('general.license') }}</th>
                                <th>{{ trans('admin/licenses/general.seat') }}</th>
                                <th>{{ trans('admin/licenses/general.user') }}</th>
                                <th>{{ trans('general.audited_by') }}</th>
                                <th>{{ trans('admin/licenses/general.seat_audit.active_use') }}</th>
                                <th>{{ trans('admin/licenses/general.seat_audit.user_confirmed') }}</th>
                                <th>{{ trans('general.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($seatAudits as $audit)
                                <tr>
                                    <td>{{ $audit->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $audit->license->name }}</td>
                                    <td>{{ $audit->license_seat_id }}</td>
                                    <td>{{ $audit->assigned_to_name ?? trans('admin/reports/general.deleted_user') }}</td>
                                    <td>{{ $audit->auditor?->display_name ?? trans('admin/reports/general.deleted_user') }}</td>
                                    <td>{{ $audit->in_use ? trans('general.yes') : trans('general.no') }}</td>
                                    <td>{{ $audit->user_confirmed ? trans('general.yes') : trans('general.no') }}</td>
                                    <td>{{ $audit->notes }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8">{{ trans('admin/licenses/general.pool_audit.no_audits') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $seatAudits->links() }}
                </div>
            </div>
        </div>
    </div>
@stop
