var logMail = 'root@advancedwebtesting.com';
var logBuf = [];
function log(str) {
	logBuf.push(str);
	Logger.log(str);
}
function mailLog(subject) {
	MailApp.sendEmail(logMail, subject, logBuf.join("\n"));
	logBuf = [];
}

function main() {
	var kk = AdWordsApp.keywords().withCondition('TopOfPageCpc > 0').orderBy("FirstPageCpc ASC").get();
	while (kk.hasNext()) {
		var k = kk.next();
		log('id: ' + k.getId() + ', text: ' + k.getText() + ', 1st page bid: ' + k.getFirstPageCpc() + ', top of page bid: ' + k.getTopOfPageCpc());
	}
	mailLog('Keyword Bids');
}
