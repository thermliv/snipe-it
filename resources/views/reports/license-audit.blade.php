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
                    <a href="{{ route('reports.license-audit.export', 'pool') }}" class="btn btn-theme btn-sm pull-right" aria-label="{{ trans('general.export_all_to_csv') }} - {{ trans('admin/licenses/general.pool_audit.pool_history') }}">
                        <x-icon type="download" /> {{ trans('general.export_all_to_csv') }}
                    </a>
                </div>
                <div class="box-body table-responsive">
                    <p>{{ trans('admin/licenses/general.pool_audit.export_page_help') }}</p>
                    <table id="licensePoolAuditReport" class="table table-striped snipe-table"
                           data-id-table="licensePoolAuditReport" data-cookie-id-table="licensePoolAuditReport"
                           data-side-pagination="client" data-pagination="false" data-search="false"
                           data-advanced-search="false" data-show-refresh="false" data-show-columns="false" data-show-export="true"
                           data-export-options='{"fileName":"license-pool-audit-report-{{ date('Y-m-d') }}"}'>
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
                            @foreach ($poolAudits as $audit)
                                <tr>
                                    <td>{{ $audit->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $audit->license->name }}</td>
                                    <td>{{ $audit->auditor?->display_name ?? trans('admin/reports/general.deleted_user') }}</td>
                                    <td>{{ $audit->recorded_seats }}</td>
                                    <td>{{ $audit->verified_seats }}</td>
                                    <td>{{ $audit->notes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $poolAudits->links() }}
                </div>
            </div>

            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('admin/licenses/general.pool_audit.seat_history') }}</h2>
                    <a href="{{ route('reports.license-audit.export', 'seat') }}" class="btn btn-theme btn-sm pull-right" aria-label="{{ trans('general.export_all_to_csv') }} - {{ trans('admin/licenses/general.pool_audit.seat_history') }}">
                        <x-icon type="download" /> {{ trans('general.export_all_to_csv') }}
                    </a>
                </div>
                <div class="box-body table-responsive">
                    <p>{{ trans('admin/licenses/general.pool_audit.export_page_help') }}</p>
                    <table id="licenseSeatAuditReport" class="table table-striped snipe-table"
                           data-id-table="licenseSeatAuditReport" data-cookie-id-table="licenseSeatAuditReport"
                           data-side-pagination="client" data-pagination="false" data-search="false"
                           data-advanced-search="false" data-show-refresh="false" data-show-columns="false" data-show-export="true"
                           data-export-options='{"fileName":"license-seat-audit-report-{{ date('Y-m-d') }}"}'>
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
                            @foreach ($seatAudits as $audit)
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
                            @endforeach
                        </tbody>
                    </table>
                    {{ $seatAudits->links() }}
                </div>
            </div>
        </div>
    </div>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
