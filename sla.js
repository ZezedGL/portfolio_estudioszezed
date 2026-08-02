// Google Apps Script - Servidor Backend

function doPost(e) {
  const data = JSON.parse(e.postData.contents);
  const action = data.action;
  const sheet = SpreadsheetApp.getActiveSpreadsheet();

  if (action === 'cadastro') {
    const userSheet = sheet.getSheetByName('Usuarios');
    userSheet.appendRow([data.email, data.senha, data.tipo]); // tipo: 'admin' ou 'cliente'
    return response({ status: 'sucesso', message: 'Usuário cadastrado!' });
  }

  if (action === 'login') {
    const userSheet = sheet.getSheetByName('Usuarios');
    const users = userSheet.getDataRange().getValues();
    for (let i = 1; i < users.length; i++) {
      if (users[i][0] === data.email && users[i][1] === data.senha) {
        return response({ status: 'sucesso', tipo: users[i][2] });
      }
    }
    return response({ status: 'erro', message: 'Credenciais inválidas.' });
  }

  if (action === 'criarEvento') {
    const eventSheet = sheet.getSheetByName('Eventos');
    const id = 'EVT-' + Date.now();
    eventSheet.appendRow([id, data.nome, data.data, data.valor]);
    return response({ status: 'sucesso', message: 'Evento criado com sucesso!' });
  }

  if (action === 'confirmarInscricao') {
    const inscSheet = sheet.getSheetByName('Inscricoes');
    const codigoUnico = 'TICKET-' + Math.random().toString(36).substr(2, 9).toUpperCase();
    inscSheet.appendRow([data.email, data.eventoId, codigoUnico, 'PAGO', new Date()]);
    return response({ status: 'sucesso', codigo: codigoUnico });
  }
}

function doGet(e) {
  const action = e.parameter.action;
  const sheet = SpreadsheetApp.getActiveSpreadsheet();

  if (action === 'listarEventos') {
    const eventSheet = sheet.getSheetByName('Eventos');
    const rows = eventSheet.getDataRange().getValues();
    const eventos = [];
    for (let i = 1; i < rows.length; i++) {
      eventos.push({ id: rows[i][0], nome: rows[i][1], data: rows[i][2], valor: rows[i][3] });
    }
    return response({ status: 'sucesso', eventos: eventos });
  }
}

function response(data) {
  return ContentService.createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}