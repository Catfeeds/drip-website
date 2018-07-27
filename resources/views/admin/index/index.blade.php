
@extends('admin.layouts.master')

@section('css')
    @parent
    <link rel="stylesheet" href="{{asset('plugins/datatables/media/css/dataTables.bootstrap.min.css')}}">
@stop

@section('content')

@stop
@section('scripts')
    <!-- DataTables -->
    <script type="text/javascript" src="{{asset('plugins/datatables/media/js/jquery.dataTables.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('plugins/datatables/media/js/dataTables.bootstrap.min.js')}}"></script>

    <script>
        $(function() {
            $('#articles-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength:100,
                ajax: '{!! route('admin.blog.article.lists') !!}',
                order: [[0, "desc"]],
                type:'POST',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'title', name: 'title' },
                    { data: 'category_id', name: 'category_id' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action' }
                ]
            });
        });
    </script>
@stop