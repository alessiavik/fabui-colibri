<?php
/**
 * 
 * @author Daniel Kesler
 * @version 1.0
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
?>

<script type="text/javascript">

	var idFile <?php echo $file_id != '' ? ' = '.$file_id : ''; ?>; //file to create
	var idTask <?php echo $runningTask ? ' = '.$runningTask['id'] : ''; ?>;
	
	$(document).ready(function() {
		$('[data-toggle="tooltip"]').tooltip();
	});
	
	function checkWizard()
	{
		var step = $('.wizard').wizard('selectedItem').step;
		console.log(step);
		switch(step){
			case 1: // Select file
				disableButton('.btn-prev');
				if(idFile)
					enableButton('.btn-next');
				else
					disableButton('.btn-next');
				$('.btn-next').find('span').html('Next');
				
				break;
			case 2: // Get Ready
				enableButton('.btn-prev');
				//~ disableButton('.btn-next');
				enableButton('.btn-next');
				$('.btn-next').find('span').html(_("Print"));
				break;
				
			case 3: // Execution
				<?php if($runningTask): ?>;
				// do nothing
				<?php else: ?>
					startTask();
				<?php endif; ?>
				return false;
				break;
			case 4:
				
				$('.btn-next').find('span').html('');
		}
	}
	
	function startTask()
	{
		openWait('<i class="fa fa-spinner fa-spin "></i>' + "<?php echo _('Preparing {0}');?>".format("<?php echo _(ucfirst($type)); ?>"), _("Checking safety measures...") );
		
		var calibration = $('input[name=calibration]:checked').val();
		
		var data = {
			idFile:idFile,
			skipEngage:skipEngage,
			calibration:calibration
			};
			
		$.ajax({
			type: 'post',
			data: data,
			url: '<?php echo site_url($start_task_url); ?>',
			dataType: 'json'
		}).done(function(response) {
			if(response.start == false){
				$('.wizard').wizard('selectedItem', { step: 2 });
				fabApp.showErrorAlert(response.message);
			}else{
				idTask = response.id_task;
				fabApp.resetTemperaturesPlot(1);
				setTimeout(initGraph, 1000);
				updateZOverride(0);
				initRunningTaskPage();
				ga('send', 'event', 'print', 'start', 'print started');
			}
			closeWait();
		})
	}
	
</script>
