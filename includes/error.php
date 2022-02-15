<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 에러 메시지를 출력한다.
 *
 * @file /includes/error.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 15.
 */
?>
<main data-type="error" data-mode="<?php echo $error->debugMode === true ? 'debug' : 'normal'; ?>">
	<section data-role="message">
		<h4><?php echo $error->title; ?></h4>
		
		<article>
			<?php echo $error->prefix !== null ? '<label>'.$error->prefix.'</label>' : ''; ?>
			<b><?php echo $error->message; ?></b>
			<?php echo $error->suffix !== null ? '<small>'.$error->suffix.'</small>' : ''; ?>
		</article>
	</section>
	
	<?php if ($error->debugMode === true) { ?>
	<section data-role="debug">
		<ul>
			<li><button type="button" class="selected">Stack trace</button></li>
			<li><button type="button">Request</button>
			<li><button type="button">Session</button>
		</ul>
		
		<article>
			<aside>
				<div>
					<ul>
						<li class="title">
							<button type="button" data-action="toggle"><i></i></button>
						</li>
						<?php foreach ($error->stacktrace as $index=>$trace) { ?>
						<li class="stack">
							<button type="button" data-index="<?php echo $index; ?>"<?php echo $trace->file == $error->file && $trace->line == $error->line ? ' class="selected"' : ''; ?>>
								<label><?php echo $trace->file; ?></label>
								<p>
									<i><?php echo count($error->stacktrace) - $index; ?></i>
									<b><?php echo $trace->method; ?></b>
									<small><?php echo $trace->line; ?></small>
								</p>
							</button>
						</li>
						<?php } ?>
					</ul>
				</div>
			</aside>
			
			<div>
				<?php foreach ($error->stacktrace as $index=>$trace) { ?>
				<ul data-role="code" data-index="<?php echo $index; ?>"<?php echo $trace->file == $error->file && $trace->line == $error->line ? ' class="selected"' : ''; ?>>
					<li class="file">
						<label>
							<?php echo $trace->method; ?>
						</label>
						<b><?php echo $trace->file; ?></b>
					</li>
					<?php foreach ($trace->lines as $line=>$code) { ?>
					<li<?php echo $trace->line == $line ? ' class="focused"' : ''; ?>><small><?php echo $line; ?></small><pre><?php echo str_replace(['<','>',"\t"],['&lt;','&gt;','    '],$code); ?></pre></li>
					<?php } ?>
				</ul>
				<?php } ?>
			</div>
		</article>
	</section>
	<?php } ?>
</main>
<!-- // todo: jQuery 어떻게 할건지 고민해보자! -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		let $error = $("main[data-type=error]");
		$("button[data-index]",$error).on("click",function() {
			let $button = $(this);
			let index = $button.attr("data-index");
			$("button[data-index]").removeClass("selected");
			
			$button.addClass("selected");
			
			$("ul[data-role=code]",$error).removeClass("selected");
			$("ul[data-role=code][data-index=" + index + "]",$error).addClass("selected");
		});
		
		$("button[data-action=toggle]",$error).on("click",function() {
			let $button = $(this);
			let $aside = $button.parents("div");
			$aside.toggleClass("opened");
		});
	});
</script>