### Mapeamento e Esclarecimento de Dúvidas e Conflitos
> 📌 **CONSOLIDAÇÃO DOCUMENTAL (21/09/2026):** as decisões e esclarecimentos deste ficheiro foram
> **consolidados em `especificacao_mvp.md` §3** (documento-mestre, que **prevalece**).
> Mantém-se como **fonte original** dos esclarecimentos. Mapa documental: `especificacao_mvp.md` §29.2.

Apresenta-se de seguida o mapeamento detalhado das divergências identificadas entre o documento inicial (um .pdf inicial sobre o projeto) de contabilidade e os documentos de planeamento/MVP (`.md`), acompanhado dos respetivos esclarecimentos finais definidos para o projeto:

---

#### 1. Regra de Decisão de Rotas e Limiar Financeiro
* **Conflito Identificado:** 
  * *Contabilidade.pdf:* Definia validação obrigatória de viabilidade financeira com cancelamento automático de rotas caso os limiares mínimos não fossem atingidos[cite: 5].
  * *Ficheiros `.md`:* O algoritmo automático de viabilidade foi revogado, passando a decisão de aprovar ou recusar rotas a ser estritamente manual e livre por parte do gestor, funcionando os 50€ apenas como um indicador visual de referência (`meetsReference`)[cite: 2, 3].
* **Esclarecimento / Decisão Final:** 
  * **Prioriza-se a abordagem dos ficheiros `.md`**: A decisão final de aprovação ou recusa da rota fica inteiramente nas mãos do gestor, servindo o valor de referência apenas como apoio visual e alerta nos casos aplicáveis[cite: 2, 3].

#### 2. Relação entre Funcionários e Categorias Profissionais
* **Conflito Identificado:** 
  * *Contabilidade.pdf:* Indicava alocação dinâmica estritamente baseada nas categorias específicas dos serviços agendados[cite: 5].
  * *Ficheiros `.md`:* A tabela de ligação N:N entre funcionário e categoria foi removida; as categorias funcionam apenas como filtros e agrupadores visuais, tendo os profissionais total liberdade para aceitar qualquer serviço[cite: 3].
* **Esclarecimento / Decisão Final:** 
  * **Prioriza-se a abordagem dos ficheiros `.md`**: As categorias profissionais atuam unicamente como filtros e agrupadores visuais, permitindo total flexibilidade na aceitação de serviços[cite: 3].

#### 3. Política Salarial e Comissões vs. Recibos Verdes
* **Conflito Identificado:** 
  * *Contabilidade.pdf:* Referia uma política de salários fixos para todos os funcionários, sem cálculo automático de comissões[cite: 5].
  * *Ficheiros `.md`:* Integram um Simulador de Recibos Verdes por aceitação de serviço de ambulatório (com percentagens configuráveis, por omissão 70% para o funcionário e 30% para a plataforma)[cite: 3].
* **Esclarecimento / Decisão Final:** 
  * **Prioriza-se a abordagem dos ficheiros `.md`**, com a seguinte nuance operacional: nem todos os funcionários trabalham a recibos verdes; os colaboradores com vínculo de contrato fixo desempenham funções predominantemente na loja física, enquanto os colaboradores a recibo verde operam ativamente na vertente ambulante e são sujeitos ao simulador de aceitação por serviço.

#### 4. Estrutura da Frota Móvel
* **Conflito Identificado:** 
  * *Contabilidade.pdf:* Sugeria a existência de 3 carrinhas separadas, uma por cada área (cabeleireiro, barbearia, estética)[cite: 5].
  * *Ficheiros `.md` e plano operacional:* Adotam uma única carrinha polivalente para otimização do investimento inicial[cite: 3, 5].
* **Esclarecimento / Decisão Final:** 
  * **Prioriza-se a abordagem dos ficheiros `.md`**: O projeto conta com uma única carrinha polivalente que transporta a equipa para a execução dos serviços, independentemente das respetivas especialidades originais[cite: 3, 5].

#### 5. Percentagem do Sinal de Reserva
* **Conflito Identificado:** 
  * *Contabilidade.pdf:* Menciona uma ponderação inicial de 10% ou 50% de sinal prévio[cite: 5].
  * *Ficheiros `.md`:* Fixam o sinal de 10% exclusivamente para a loja física, sendo dispensado na primeira marcação em ambulatório para mitigar barreiras de entrada[cite: 1, 3, 5].
* **Esclarecimento / Decisão Final:** 
  * **Prioriza-se a abordagem dos ficheiros `.md`**: Aplica-se estritamente o sinal de 10% no canal de loja física (com gestão e configuração centralizada no backoffice), sendo a primeira marcação em ambulatório isenta deste requisito para novos clientes[cite: 1, 3, 5].

#### 6. Interface e Apresentação do Catálogo (Serviços)
* **Conflito / Ideia Inicial:** Existia referência a uma framework visual externa (AdminLTE) e dúvidas sobre a forma de exibição do catálogo.
* **Esclarecimento / Decisão Final:** 
  * **Ignora-se a menção ao AdminLTE**.
  * A informação dos serviços é apresentada em formato de *cards*. Adota-se obrigatoriamente uma **página de detalhes dedicada para cada serviço**, encimada por um **carousel de imagens** do procedimento/resultados, descrição detalhada e estimativa padrão do tempo de execução (ex: *Corte de Cabelo: 45 min*).

#### 7. Escolha da Hora e Dinâmica de Tempos (Frontend - Agendamento)
* **Conflito Identificado:** Dúvida entre o cliente escolher uma hora exata de uma lista disponível versus a seleção de uma janela horária ampla (blocos de 2 horas).
* **Esclarecimento / Decisão Final:** 
  * O cliente escolhe a **hora inicial a partir de uma lista de horas disponíveis**, gerada com base nos agendamentos consolidados para essa cidade (aplicável ao ambulatório; irrelevante para a loja física).
  * O **tempo estimado é re-avaliado dinamicamente** sempre que o cliente adiciona ou discarta serviços durante o preenchimento do formulário (via validação de disponibilidade *server-side*). 
  * Garante-se estruturalmente que a escolha da hora ocorre **estritamente após a seleção dos serviços** (no passo seguinte do formulário) para evitar colisões temporais.

#### 8. Logística de Condução e Papel dos Funcionários
* **Conflito Identificado:** Dúvida sobre se os funcionários acumulavam funções de motorista e se existia controlo logístico de condução.
* **Esclarecimento / Decisão Final:** 
  * **Não existe o conceito de motorista dedicado** nem qualquer lógica de controlo de condução na plataforma. 
  * A carrinha funciona estritamente como meio de transporte; uma vez estacionada na morada do cliente, os serviços são executados de forma polivalente por qualquer funcionário presente, independentemente de especialidades restritas.

#### 9. Flexibilidade Horária e Rotas Multicidades
* **Conflito Identificado:** Como gerir horários rígidos face à realidade de rotas complexas ou extensas.
* **Esclarecimento / Decisão Final:** 
  * **Horários da Carrinha:** São tendencialmente flexíveis. Caso o término de um agendamento excede ligeiramente o limite padrão do fim do dia (19:00h), o sistema contempla exceções para o autorizar.
  * **Rotas Multicidades:** É permitida a criação de rotas que englobem agendamentos de múltiplas cidades num único dia (ex: *Évora -> Arraiolos -> Évora*), desde que os agendamentos estejam cronologicamente ordenados e o sistema valide na base de dados se existe **espaçamento de tempo suficiente** para a deslocação física segura entre cidades.
  * **Alertas de Custos:** Como estas deslocações multicidades aumentam significativamente os custos de combustível (igualmente divididos pelos clientes no pagamento final), o backoffice exibe um **alerta padronizado de custos e viabilidade** posicionado junto ao indicador de referência dos 50€.

#### 10. Pagamentos, Sinal (10/90), Simulação e Falhas de Internet
* **Conflito Identificado:** Regras de divisão de pagamentos, tratamento de falhas de rede no terreno e emissão de recibos.
* **Esclarecimento / Decisão Final:** 
  * Mantém-se a política de **sinal de 10%** no agendamento (com configuração generalizada numa secção dedicada do backoffice). Os restantes **90%** são cobrados no término do serviço.
  * No MVP, todos os pagamentos e opções (Dinheiro, Multibanco, MB Way) são **simulados de forma realista (*dummy*)**.
  * Em caso de falhas de internet no terreno, o sistema simula o pagamento restrito a numerário. A emissão de recibos manuais é tratada como **possível implementação futura** (a alinhar com as restantes melhorias planeadas).

#### 11. Cancelamentos, Janela de 24 Horas e Notificações ao Cliente
* **Conflito Identificado:** Como tratar cancelamentos de última hora, penalizações e o destino de agendamentos não incluídos em rotas.
* **Esclarecimento / Decisão Final:** 
  * Nenhum gestor pode criar rotas com agendamentos a menos de 24 horas da execução. 
  * Se um agendamento atingir a marca de 24 horas sem ter sido incluído numa rota, ele é **automaticamente descartado/cancelado** das listagens ativas (mas retido na base de dados para fins analíticos e algoritmos futuros).
  * O sistema despoleta automaticamente um **alerta/lembrete ao cliente** a informar da impossibilidade de execução, sugerindo alternativas user-friendly como a deslocação à loja física ou reagendamento.
  * O cliente pode cancelar o seu agendamento através da plataforma; caso o faça após estar associado a uma rota (respeitando a antecedência), **não recebe nenhuma penalização financeira**.

Informação de reforço:


- Ignora a informação referente ao AdminLTE

- Também podes ignorar isto, já está implementado e é informação leve (se necessário poderemos inclui-la de uma forma leve no .md, mas não agora)

- Catálogo Multimédia e Descrições: A informação referente aos serviços está a ser demonstrada através de cards, pretende-se fazer uma página de detalhes para um serviço específico (se ainda não existir), mostrando toda a informação referente a esse serviço, e um carousel de imagens desse serviço por cima dessa informação.

- Devemos manter essa politica de sinal de 10% do serviço, implementando o que faltar no pagamento dos 90% finais (mas priorizar a definição já existente no .md). Relativamente ás falhas de internet no terreno, o pagamento deve ser simulado (não sei se consta num dos .md que partilhei, mas atualmente é essa a ideia que temos em mente agora, nenhum processo de pagamento oficial, está tudo a ser simulado (dummy) para já no MVP, mas seria interessante simular a escolha do metodo de pagamento de uma forma realista, com as opções fornecidas), quanto ao recibo, aponta essa informação como ainda por ser avaliada se já está ou não a ser tratada no MVP atual, se não estiver, trata-a como possivel implementação futura, junto das restantes já existentes

- Confirmo que não existe conceito de motorista dedicado, nem deve ser feito nada a esse respeito na plataforma.

- Os horários da carrinha podem de facto ser flexiveis e não coincidir com os da loja, mas essa flexibilidade deverá ser tratada como excepção (ex: quando o fim de um agendamento excede o limite do fim do dia (19:00h), deverá haver flexibilidade para o permitir.

- Rotas com agendamentos em multiplas cidades deverá ser possivel, mas não a norma, ajustar a listagem de agendamentos que o gestor vê se necessário, de forma a que ele possa incluir agendamentos de outras cidades num grupo (desde que o intervalo de tempo desses agendamentos não colida com o intervalo de tempo dos agendamentos já selecionados para a rota (os inicialmente agrupados da cidade por exemplo, se se aplicar, também nesse cenário deverá ser verificado se existe um espaçamento de tempo entre os agendamentos selecionados de diferentes cidades (consultar BD para ver se já não existe essa informação em alguma tabela), partindo do principio que os agendamentos selecionados estão ordenados por data e tempo, i.e: agendamento1:Evora, agendamento2:Evora, <verificar se existe intervalo de tempo suficiente para a deslocação de Evora para Arraiolos> agendamento3:Arraiolos, agendamento4:Arraiolos, <verificar se existe intervalo de tempo suficiente para a deslocação de Arraiolos para Evora, agendamento5:Evora, ...) Obvio que isto acresce significativamente nos custos de combustivel (que são igualmente divididos entre os clientes no pagamento final, então deverá ser mostrado um alerta sobre o assunto, por cima ou por baixo da informação de viabilidade da Rota (a dos 50%), seria bom ter a zona de alertas em listagens do backoffice bem padronizada/convencionada e definida no .md)

- Modulos Abrangentes do Dashboard: Essa informação é importante estar bem definida no .md, mas deveremos ver o que já existe sobre o assunto nos ficheiros atuais de .md e no MVP já implementado, adaptar a implementação no MVP (backoffice) que seja referente a algum destes menus, de forma a que o backoffice siga a mesma estrutura, e trabalhar a restante informação com base nas convenções existentes, tratando-as como futuras implementações (juntando-as com as já existentes) (Nota: deverá ser priorizada qualquer futura implementação sobre Fornecedores)

- Atualmente no MVP, creio que o cliente escolhe a hora inicial de uma lista de horas disponveis, com base nos agendamentos já consolidados (para essa cidade, em serviços de ambulatorio, não se aplica aos da loja visto que nesses a cidade não é preenchida nem relevante) (agendamento consolidado: agendamento com todos os seus serviços já aceites por funcionários) (confirmar). Uma coisa que reparei e que deve ser confirmada, é que o tempo estimado deve ser re-avaliado sempre que o cliente inclui ou discarta serviços durante o processo de preenchimento do formulário de agendamento (isto se já não tiver a ser feito, tanto em ambulatorio como em loja, caso não esteja, deverá ser utilizada e readaptada a implementação já existente de validação de disponibilidade no server side), isto é importante, uma vez que se o tempo estimado ao agendamento colidir com qualquer agendamento consolidado (nessa cidade em ambulatorio, sem cidade em loja), os intervalos de tempo deverão mudar, ou então, melhor ainda, garantir que no formulario de agendamento (tanto ambulatorio como loja), a escolha da hora somente ocorre após a escolha dos serviços (no passo/form-step seguinte), de qualquer forma, deverá ser validada e se necessário re-adaptada a implementação e respetiva descrição nos ficheiros de planeamento (que eventualmente se centralizarão neste .md final que estamos a criar).

- Funcionarios a Acumular Funções de Condução: Nada disso, os serviços dos agendamentos poderão ser executados por todos os funcionarios que forem na carrinha, a carrinha vai estar parada na morada/domicilio do cliente, então não há necessidade de controlar essa logistica, é inexistente, não deverá existir logistica na plataforma relacionada a quem conduz a carrinha.

- Caso um cliente consiga cancelar um agendamento após 24 horas, signfica que esse agendamento já se encontra incluido numa rota criada por um gestor, isto porque nenhum gestor deverá ser capaz de criar rotas com agendamentos a serem executados a menos de 24 horas da data em que a criam, esses agendamentos deverão ser automaticamente discartados/cancelados, isto está em sintonia com o requisito do lembrete/aviso ao cliente da impossibilidade de executar o agendamento dele a 24 horas da execução do mesmo, caso não se encontre já associado a uma rota. De notar que estes agendamentos já nem devem aparecer nas listagens do backoffice (de aceitação por parte de funcionarios, nem de inclusão em rotas por parte dos gestores, deverão ser mantidos na base de dados, por motivos de retenção de informação para algoritmos e simuladores futuros que possam fazer sentido...). Relativamente á penalização pelo agendamento ter sido cancelado após 24 horas, o cliente não deverá receber nenhuma penalização. No entanto, acho que faz todo o sentido haver uma secção no backoffice para configurar o sinal a ser inicialmente cobrado aos clientes, (deverá ser verificada se já existe uma secção apropriada para incluir esta configuração, incluindo a possibilidade de generalizar uma secção já existente para que possa envolver este contexto).

- Este alerta/lembrete especifico ao cliente deverá ser gerado caso já esteja a 24h da execução do mesmo, e o seu agendamento não tenha sido incluido numa rota por um gestor, a parte de sugestão a uma loja fisica ou reagendamento é muito util e user friendly, se ainda não houver implementação sobre isso no MVP, deveremos incluir isso nos requisitos do .md que estamos a criar.



Extra:

- Verificar também se já existe no MVP ou nos requisitos, a possibilidade de um cliente de facto conseguir cancelar um agendamento (deverá por faze-lo).



Mostra-me agora, com base nestes esclarecimentos, o que pretendes incluir e uniformizar no .md final 

