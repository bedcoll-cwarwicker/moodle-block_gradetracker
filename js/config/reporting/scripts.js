function reporting_bindings(){
    
    $('input.gt_run_report').off('click');
    $('input.gt_run_report').on('click', function(){
                
        // Disable input button while running
        var btn = $(this);
        btn.prop('disabled', true);
        btn.val( M.util.get_string('running', 'block_gradetracker') + '...' );
        
        var params = {};
        var report = $(this).attr('report');
        params.report = report;
        params.btn = btn.attr('id');
        params.params = [];
        
        $('.report_option').each( function(){
            
            var nm = $(this).attr('name');
            var vl = $(this).val();
            
            params.params.push( { name: nm, value: vl } );
            
        } );
        
        gtAjaxProgress( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'download_report', params: params }, '#gt_report_progress', function(data){
            
            // Reset button
            btn.removeProp('disabled');
            btn.val( M.util.get_string('run', 'block_gradetracker') );
            
            // Download file
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/download.php?f='+data.file+'&t='+data.time;
            
        });
        
    });
    
    
    $('#gt_log_search_qual').off('change');
    $('#gt_log_search_qual').on('change', function(){
        
        $('#gt_log_search_unit').html('<option value=""></option>');

        var qID = $(this).val();
        if (qID == ''){
            return;
        }
        
        $('#gt_log_search_load').show();
        
        var params = {qualID: qID};
        
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_qual_units', params: params}, function(data){
            
            var units = $.parseJSON(data);
            $.each(units['order'], function(indx, uID){
                $('#gt_log_search_unit').append('<option value="'+uID+'">'+units['units'][uID]+'</option>');
            });
            
            $('#gt_log_search_load').hide();
            
        });
        
    });
    
}


