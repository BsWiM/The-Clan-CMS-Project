
<?php $this->load->view(ADMINCP . 'header'); ?>

<?php $this->load->view(ADMINCP . 'sidebar'); ?>

 <script type="text/javascript">
	$(function() {
		var cct = $.cookie('ci_csrf_token');
		
		$("#members tbody").sortable({
			update:function(i) {
				$.ajax({
					type: "POST",
					url: "<?php echo base_url(); ?><?php echo ADMINCP; ?>squads/order_members/<?php echo $squad->squad_id; ?>",
					data: $(this).sortable("serialize") + '&ci_csrf_token=' + cct
				});
				
				$("#move").html('<div class="alert">The member was successfully moved!</div><br />');
			}
		});

		$("#members tbody").disableSelection();

	});
	
	function deleteConfirm()
	{
    	var answer = confirm("Are you sure you want to delete this member? Once deleted, there will be no way to recover the member's stats!")
    	if (answer)
		{
        	document.messages.submit();
    	}
    
    	return false;  
	} 
</script> 

<?php echo form_open(ADMINCP . 'squads/edit/' . $squad->squad_id); ?>
<div id="main">
<style>
.icons ul {
    height: 80px;
    list-style: none outside none;
    text-align: center;
    margin-left: 275px;
    padding: 0;
}
.icons li {
    display: inline-block;
    float: left;
    width: 34px;
    border: 1px solid #444;
    margin: 0 2px 2px 0;
    padding: 0px 2px;
}
</style>
	<div class="box">
		<div class="tabs">
		<ul>
			<li><span class="left"></span><span class="middle"><?php echo anchor(ADMINCP . 'squads', 'Squads'); ?></span><span class="right"></span></li>
			<li><span class="left"></span><span class="middle"><?php echo anchor(ADMINCP . 'squads/add', 'Add Squad'); ?></span><span class="right"></span></li>
			<li class="selected"><span class="left"></span><span class="middle"><?php echo anchor(ADMINCP . 'squads/edit/' . $squad->squad_id, 'Edit Squad: ' . $squad->squad_title); ?></span><span class="right"></span></li>
			<li><span class="left"></span><span class="middle"><?php echo anchor(ADMINCP . 'squads/icons', 'Squad Icons'); ?></span><span class="right"></span></li>
		</ul>
		</div>
		
		<div class="header">
			<?php echo heading('Edit Squad: ' . $squad->squad_title, 4); ?>
		</div>
		<div class="content">
			<div class="inside">
				
				<?php if(validation_errors()): ?>
				<div class="alert">
					<?php echo validation_errors(); ?>
				</div>
				<?php endif; ?>
				
				<?php if($this->session->flashdata('message')): ?>
				<div class="alert">
					<?php echo $this->session->flashdata('message'); ?>
				</div>
				<?php endif; ?>
			
				<div class="required-field required">Required Field</div>
				<?php echo br(); ?>
				
				<div class="subheader">
					<?php echo heading('Squad Information', 4); ?>
				</div>
		
				<div class="label required">Title</div>
				
				<?php 
				$data = array(
					'name'		=> 'title',
					'size'		=> '30',
					'class'		=> 'input'
				);

				echo form_input($data, set_value('title', $squad->squad_title)); ?>
				<?php echo br(); ?>
				<div class="description">The name of your squad</div>
		
				<!-- Squad Status -->
				<div class="label required">Status</div>
				<?php
					$options = array(
						'0' => 'Inactive',
						'1'	=> 'Active'
					);
					
				echo form_dropdown('status', $options, set_value('status', $squad->squad_status), 'class="input"'); ?>
				<?php echo br(); ?>
				<div class="description">The status of the squad</div>
				
				<!-- Squad Priority -->
				<div class="label required">Priority</div>
				
				<?php 
				$data = array(
					'name'		=> 'priority',
					'size'		=> '30',
					'class'		=> 'input'
				);

				echo form_input($data, set_value('priority', $squad->squad_priority)); ?>
				<?php echo br(); ?>
				<div class="description">The order in which this squad should appear</div>
				
				<!-- Squad Icon selector -->
				<div class="label required">Squad Icon</div>
				<div class="icons">
					<?php if($icons): ?>
						<ul style="list-style:none;">
							<?php foreach($icons as $icon): ?>
								<li>
									<div><?php echo img(array('src'=> IMAGES. 'squad_icons/' . $icon->icon, 'height' =>32, 'width' =>32)); ?></div>
									<div>
										<?php 
											if($icon->icon == $squad->squad_icon): 
												echo form_radio('icon', $icon->icon,  set_radio('icon', $squad->squad_icon, TRUE));
											else:
												echo form_radio('icon', $icon->icon);
											endif;
										?>
									</div>
								</li>
							<?php endforeach;?>
					<?php else: ?>
						<p>There are no squad icons. <?php echo anchor(ADMINCP . 'squads/icons', 'Add some here'); ?> </p>
						</ul>
					<?php endif; ?>
				</div>
		
			</div>
		</div>
		<div class="footer"></div>
	</div>
	
	<div class="space"></div>
	
	<div class="box">
		
		<div class="header">
			<?php echo heading('Squad Members', 4); ?>
		</div>
		<div class="content">
			<div class="inside">
				
			<div id="move"></div>
			
			<table id="members">
				<thead>
					<tr>
						<th width="20%">Username</th>
						<th width="35%">Player Name</th>
						<th width="35%">Role</th>
						<th width="10%">Actions</th>
					</tr>
				</thead>
			
				<tbody>
					<?php if($members): ?>
						<?php foreach($members as $member): ?>
						<tr id="member_<?php echo $member->member_id; ?>" class="move">
							<td><?php echo br(); ?><?php echo anchor(ADMINCP . 'users/edit/'. $member->user_id, $member->user_name); ?><?php echo br(2); ?></td>
							<td><?php
									$data = array(
										'name'		=> 'titles[' . $member->member_id . ']',
										'size'		=> '30',
									);

									echo form_input($data, set_value('titles[' . $member->member_id . ']', $member->member_title)); ?></td>
							<td><?php
									$data = array(
										'name'		=> 'roles[' . $member->member_id . ']',
										'size'		=> '30',
									);

									echo form_input($data, set_value('roles[' . $member->member_id . ']', $member->member_role)); ?></td>
							<td><?php echo anchor(ADMINCP . 'squads/delete_member/' . $member->member_id, img(array('src' => ADMINCP_URL . 'images/delete.png', 'alt' => 'Delete')), array('title' => 'Delete', 'onclick' => "return deleteConfirm();")); ?></td>
						</tr>
						<?php endforeach; ?>
					<?php else: ?>
					<tr>
						<td colspan="4">There are currently no members for this squad.</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
				<?php 
					$data = array(
						'name'		=> 'update_squad',
						'class'		=> 'submit',
						'value'		=> 'Update Squad'
					);
				
				echo form_submit($data); ?>
				<div class="clear"></div>
		
			</div>
		</div>
		<div class="footer"></div>
	</div>
	<?php echo form_close(); ?>
	
	<div class="space"></div>
	
	<div class="box">
		
		<div class="header">
			<?php echo heading('Available Users', 4); ?>
		</div>
		<div class="content">
			<div class="inside">
				
			<table>
				<thead>
					<tr>
						<th width="20%">Username</th>
						<th width="35%">Player Name</th>
						<th width="35%">Role</th>
						<th width="10%">Actions</th>
					</tr>
				</thead>
			
				<tbody>
					<?php if($users): ?>
						<?php foreach($users as $user): ?>
						<?php echo form_open(ADMINCP . 'squads/add_member/' . $user->user_id); ?>
						<?php echo form_hidden('squad_id', $squad->squad_id); ?>
						<?php echo form_hidden('user_id', $user->user_id); ?>
						<tr>
							<td><?php echo br(); ?><?php echo anchor(ADMINCP . 'users/edit/'. $user->user_id, $user->user_name); ?><?php echo br(2); ?></td>
							<td><?php
									$data = array(
										'name'		=> 'titles[' . $user->user_id . ']',
										'size'		=> '30',
										'value'	=>	$user->user_name
									);

									echo form_input($data, set_value('player[' . $user->user_id . ']', '')); ?></td>
							<td><?php
									$data = array(
										'name'		=> 'roles[' . $user->user_id . ']',
										'size'			=>	'30',
										'value'		=> 	'member'
									);

									echo form_input($data, set_value('role[' . $user->user_id . ']', '')); ?></td>
							<td><input type="image" name="add_member" src="<?php echo ADMINCP_URL . 'images/add.png'; ?>" title="Add" alt="Add" /></td>
						</tr>
						<?php echo form_close(); ?>
						<?php endforeach; ?>
					<?php else: ?>
					<tr>
						<td colspan="4">There are currently no users avaialable to add.</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
			
				<div class="clear"></div>
		
			</div>
		</div>
		<div class="footer"></div>
	</div>
	
</div>

<?php $this->load->view(ADMINCP . 'footer'); ?>