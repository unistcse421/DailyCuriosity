$(function() {
	var topic_id = 0, cache = {};
	
	var parse_bracket = function(match, p1) {
		var t = p1.split(":");
		
		if(p1.match(/\d+:\d+:\d+/) && parseInt(t[0]) && parseInt(t[1]) && parseInt(t[2]))
		{
			return "<a href=\"#post" + t[0] + "\" data-toggle=\"tooltip\" data-dc-action=\"follow\" data-action-target=\"" + p1 + "\">" + p1 + "</a>";
		}
		else if(t.length >= 2 && p1.charAt(0) == "@")
		{
			return "<strong>" + t[0] + ":</strong>" + p1.substr(p1.indexOf(":") + 1);
		}
		else if(p1.charAt(0) == "@")
		{
			return "<strong>" + p1 + "</strong>";
		}
		else if(p1.charAt(0) == "#")
		{
			return "<a href=\"#\" data-dc-action=\"search\" data-action-target=\"" + p1.substr(1) + "\">" + p1 + "</a>";
		}
		return match;
	};
	
	var make_panel = function(post_id, title, position, arr, is_topic) {
		var result = "<div class=\"panel panel-default\" id=\"post" + post_id + "\"><div class=\"panel-heading\"><h3 class=\"panel-title\">";
		
		if(position)
		{
			result += "<span class=\"label label-primary\">" + position + "</span> ";
		}
		else if(topic_id && !is_topic)
		{
			result += "<span class=\"label label-default\">" + "의견 없음" + "</span> ";
		}
		
		result += title + "</h3></div><div class=\"panel-body\">";
		
		for(var i = 0; i < arr.length; ++i)
		{
			result += "<p>" + arr[i].body.replace(/\[([^\]]+)\]/g, parse_bracket) + " <span class=\"text-muted\" data-dc-action=\"history\" data-post-id=\"" + post_id + "\" data-paragraph-id=\"" + arr[i].id + "\" data-toggle=\"tooltip\" title=\"" + arr[i].last_modified + "\">[" + post_id + ":" + arr[i].id + ":" + arr[i].revision + "]</span> <span class=\"text-muted\" data-dc-action=\"write\" data-action-subtype=\"" + (is_topic ? "topic" : "post") + "\" data-action-target=\"" + post_id + ":" + arr[i].id + "\"><span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\"></span></span></p>";
		}
		
		result += "</div></div>";
		return result;
	};
	
	var update_event_handlers = function() {		
		$("[data-dc-action=\"list\"]").off("click").click(function(event) {
			topic_id = 0;
			read_current_topic(null);
			return false;
		});
		
		$("[data-dc-action=\"view\"]").off("click").click(function(event) {
			topic_id = event.target.dataset.actionTarget;
			read_current_topic(null);
			return false;
		});
		
		$("[data-dc-action=\"write\"]").off("click").click(function(event) {
			if(!event.currentTarget.dataset.actionSubtype)
			{
				return;
			}
			
			var subtype = event.currentTarget.dataset.actionSubtype;
			var target = event.currentTarget.dataset.actionTarget;
			var mode = +!parseInt(target);
			var post_id = target.split(":")[0];
			var paragraph_id = target.split(":")[1] || 0;
			
			if(mode)
			{
				$("#entryTitle").val("");
				$("#entryText").val("");
				$("#entryTitleGroup").show();
			}
			else
			{
				$.post("dc.php", JSON.stringify({type: "get", post_id: post_id, paragraph_id: paragraph_id}), function(data) {
					$("#entryText").val(data.body);
				}, "json");
				$("#entryTitleGroup").hide();
			}
			$("#writeModalLabel").html(({topic: ["주제의 문단 수정", "새 주제 작성"], post: ["의견의 문단 수정", "이 주제에 대한 새 의견 작성"]})[subtype][mode]);
			$("#writeModal .btn-primary").off("click").click(function() {
				$.post("dc.php", JSON.stringify({type: ["modify", "create"][mode] + "_" + subtype, topic_id: topic_id, post_id: post_id, paragraph_id: paragraph_id, title: $("#entryTitle").val(), text: $("#entryText").val()}), function(data) {
					if(subtype == "topic" && mode)
					{
						topic_id = data.id;
						data.id = null;
					}
					read_current_topic(data && data.id);
				}, "json");
				$("#writeModal").modal("hide");
			});
			$("#writeModal").modal("show");
			return false;
		});
		
		$("[data-dc-action=\"history\"]").off("click").click(function(event) {
			var post_id = event.target.dataset.postId;
			var paragraph_id = event.target.dataset.paragraphId;
			$("#historyModalLabel").html("문단 변경 이력");
			$("#historyModalBody").empty();
			$("#historyModal").modal("show");
			$.post("dc.php", JSON.stringify({type: "history", post_id: post_id, paragraph_id: paragraph_id}), function(data) {
				for(var i = 0; i < data.length; ++i)
				{
					$("#historyModalBody").append("<p><strong>" + data[i].last_modified + ":</strong> " + data[i].body + " <span class=\"text-muted\">[" + post_id + ":" + paragraph_id + ":" + data[i].revision + "]</span></p>");
				}
			}, "json");
			return false;
		});
		
		$("[data-dc-action=\"follow\"]").off("click").click(function(event) {
			var post = $("#post" + event.target.dataset.actionTarget.split(":")[0]);
			if(post.length)
			{
				$("body").animate({scrollTop: post.offset().top}, 2250);
			}
			
			return false;
		});
		
		$("[data-dc-action=\"search\"]").off("click").click(function(event) {
			var target = event.target.dataset.actionTarget;
			$("#historyModalLabel").html("키워드 <span class=\"text-muted\">[#</span>" + target + "<span class=\"text-muted\">]</span>");
			$("#historyModalBody").empty();
			$("#historyModal").modal("show");
			$.post("dc.php", JSON.stringify({type: "search", keyword: target}), function(data) {
				for(var i = 0; i < data.length; ++i)
				{
					$("#historyModalBody").append("<p>" + data[i].body.replace(/\[([^\]]+)\]/g, function(match, p1) {
						if(p1 == "#" + target)
						{
							return "<strong>" + p1 + "</strong>";
						}
						return p1.charAt(0) == "@" || p1.charAt(0) == "#" || p1.match(/\d+:\d+:\d+/) ? p1 : match;
					}) + " <span class=\"text-muted\">[" + data[i].post_id + ":" + data[i].id + ":" + data[i].revision + "] " + data[i].last_modified + "</span></p>");
				}
			}, "json");
			return false;
		});
		
		$("span[data-toggle=\"tooltip\"]").tooltip();
		$("a[data-dc-action=\"follow\"][data-toggle=\"tooltip\"]").tooltip({title: function() {
			var target = this.dataset.actionTarget;
			if(!(target in cache))
			{
				$.ajax({url: "dc.php", type: "POST", async: false, data: JSON.stringify({type: "get_revision", post_id: target.split(":")[0], paragraph_id: target.split(":")[1], revision: target.split(":")[2]}), success: function(data) {
					cache[target] = data.body;
				}, dataType: "json"});
			}
			return cache[target];
		}});
	};
	
	var read_current_topic = function(post_id) {
		$.post("dc.php", JSON.stringify({type: "read_topic", topic_id: topic_id}), function(data) {
			if(topic_id)
			{
				$("#main").html(make_panel(topic_id, data.title, null, data.paragraphs, true));
				
				var total = 0;
				for(var i = 0; i < data.positions.length; ++i)
				{
					data.positions[i].count = parseInt(data.positions[i].count);
					total += data.positions[i].count;
				}
				total = total ? total : 1;
				$("#positions").empty();
				for(var i = 0; i < data.positions.length; ++i)
				{
					var value = Math.round(data.positions[i].count * 100 / total);
					$("#positions").append("<div class=\"col-lg-6\"><h4>" + data.positions[i].title + "</h4><p>" + data.positions[i].description + (data.positions[i].count ? " <span class=\"text-muted\">(" + data.positions[i].count + "/" + total + ")</span>" : "") + "</p><div class=\"progress\"><div class=\"progress-bar\" role=\"progressbar\" aria-valuenow=\"" + value + "\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"min-width: 2em; width:" + value + "%;\">" + value + "%</div></div></div>");
				}
				
				$("#posts").empty();
				for(var i = 0; i < data.posts.length; ++i)
				{
					var position = null;
					for(var j = 0; j < data.positions.length; ++j)
					{
						if(data.positions[j].id == data.posts[i].position)
						{
							position = data.positions[j].title;
						}
					}
					$("#posts").append(make_panel(data.posts[i].id, data.posts[i].title, position, data.posts[i].paragraphs, false));
				}
				
				$(".jumbotron").hide();
				$("#main").show();
				$("#positions").show();
				$("#posts").show();
				$("#topics").hide();
				
				$("[data-dc-action=\"write\"][data-action-subtype=\"post\"]").show();
			}
			else
			{
				$("#topics").empty();
				for(var i = 0; i < data.posts.length; ++i)
				{
					$("#topics").append("<a href=\"#\" class=\"list-group-item\" data-dc-action=\"view\" data-action-target=\"" + data.posts[i].id + "\">" + data.posts[i].title + "</a>");
				}
				
				$(".jumbotron").show();
				$("#main").hide();
				$("#positions").hide();
				$("#posts").hide();
				$("#topics").show();
				
				$("[data-dc-action=\"write\"][data-action-subtype=\"post\"]").hide();
			}
			
			update_event_handlers();
			
			if(post_id)
			{
				$("body").animate({scrollTop: $("#post" + post_id).offset().top}, 2250);
			}
		}, "json");
	};
	
	read_current_topic(null);
});
