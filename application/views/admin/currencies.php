<div class="box">
	<div id="list-box" class="content">
	<form accept-charset="utf-8" method="post" action="<?php echo current_url(); ?>">
	<table align="center" class="list">
		<tr>
			<th width="1" style="text-align:center;"><input type="checkbox" onclick="$('input[name*=\'delete\']').prop('checked', this.checked);"></th>
			<th>Title</th>
			<th class="center">Code</th>
			<th class="center">Symbol</th>
			<th class="right">Status</th>
			<th class="right">Action</th>
		</tr>
		<?php if ($currencies) {?>
		<?php foreach ($currencies as $currency) { ?>
		<tr>
			<td class="delete"><input type="checkbox" value="<?php echo $currency['currency_id']; ?>" name="delete[]" /></td>
			<td><?php echo $currency['currency_title']; ?>
				<?php if ($currency_id === $currency['currency_id']) { ?>
				<b>(Default)</b>
				<?php } ?>
			</td>
			<td class="center"><?php echo $currency['currency_code']; ?></td>
			<td class="center"><?php echo $currency['currency_symbol']; ?></td>
			<td class="right"><?php echo ($currency['currency_status'] === '1') ? 'Enabled' : 'Disabled'; ?></td>
			<td class="right"><a class="edit" title="Edit" href="<?php echo $currency['edit']; ?>"></a></td>
		</tr>
		<?php } ?>
		<?php } else { ?>
		<tr>
			<td colspan="6" align="center"><?php echo $text_empty; ?></td>
		</tr>
		<?php } ?>
	</table>
	</form>
	</div>
</div>
