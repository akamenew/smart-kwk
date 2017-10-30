require 'rubygems'
require 'mechanize'
require 'date'

def download_transactions(user, pass, from, to)
  a = Mechanize.new

  a.agent.http.verify_mode = OpenSSL::SSL::VERIFY_NONE

  login_page = a.get('https://amt.expercash.net/')

  # Submit the login form
  login_form = login_page.form_with(:action => 'kc_login.php')
  login_form.usr_id = user
  login_form.usr_pwd = pass

  my_page = a.submit(login_form)

  # Click the "Search Button"
  search_page = my_page.link_with(:text => 'Suchen').click

  # Execute a Search
  search_form = search_page.form_with(:action => 'kc_transaktionen_erg.php')
  search_form.radiobuttons_with(:name => 's_method')[1].check
  search_form.pay_von_tag = from.day
  search_form.pay_von_monat = from.month
  search_form.pay_von_jahr = from.year

  search_form.pay_bis_tag = to.day
  search_form.pay_bis_monat = to.month
  search_form.pay_bis_jahr = to.year

  result_page = a.submit(search_form)

  # Find the result
  result = result_page.search('/html/body/form/table[2]/tr/td/table/tr/td/table/tr/td').text.match /Ihre Suchanfrage lieferte (.*?) Treffer/
  result_number = result[1]

  #print "Sales von #{from.day}.#{from.month}.#{from.year} bis #{to.day}.#{to.month}.#{to.year}:\n#{result_number}\n"

  # Export the result to CSV and download the file
  download_url = result_page.link_with(:text => '[Exportieren]').uri.to_s
  #puts "Downloading #{download_url}..."
  filename = 'transactions.csv'
  a.get('https://amt.expercash.net/' + download_url).save! filename
  
  return filename
end


def download_backcharges(user, pass, from, to)
  a = Mechanize.new

  a.agent.http.verify_mode = OpenSSL::SSL::VERIFY_NONE

  login_page = a.get('https://amt.expercash.net/')

  # Submit the login form
  login_form = login_page.form_with(:action => 'kc_login.php')
  login_form.usr_id = user
  login_form.usr_pwd = pass

  my_page = a.submit(login_form)

  # Click the "Search Button"
  search_page = my_page.link_with(:text => 'Rücklastschriften').click

  # Execute a Search
  search_form = search_page.form_with(:action => 'kc_transaktionen_erg.php')
  search_form.rl_von_tag = from.day
  search_form.rl_von_monat = from.month
  search_form.rl_von_jahr = from.year

  search_form.rl_bis_tag = to.day
  search_form.rl_bis_monat = to.month
  search_form.rl_bis_jahr = to.year

  result_page = a.submit(search_form)

  # Find the result
  result = result_page.search('/html/body/form/table[2]/tr/td/table/tr/td/table/tr/td').text.match /Ihre Suchanfrage lieferte (.*?) Treffer/
  result_number = result[1]

  #print "Rücklastschriften von #{from.day}.#{from.month}.#{from.year} bis #{to.day}.#{to.month}.#{to.year}:\n#{result_number}\n"

  # Export the result to CSV and download the file
  download_url = result_page.link_with(:text => '[Exportieren]').uri.to_s
  #puts "Downloading #{download_url}..."
  filename = 'backcharges.csv'
  a.get('https://amt.expercash.net/' + download_url).save! filename
  
  return filename
end


def download_payments(user, pass, from, to)
  a = Mechanize.new

  a.agent.http.verify_mode = OpenSSL::SSL::VERIFY_NONE

  login_page = a.get('https://amt.expercash.net/')

  # Submit the login form
  login_form = login_page.form_with(:action => 'kc_login.php')
  login_form.usr_id = user
  login_form.usr_pwd = pass

  my_page = a.submit(login_form)

  # Click the "Search Button"
  search_page = my_page.link_with(:text => 'Zahlungseingänge').click

  # Execute a Search
  search_form = search_page.form_with(:action => 'kc_transaktionen_erg.php')
  search_form.checkbox_with(:name => 'zeitraum').check
  search_form.von_tag = from.day
  search_form.von_monat = from.month
  search_form.von_jahr = from.year

  search_form.bis_tag = to.day
  search_form.bis_monat = to.month
  search_form.bis_jahr = to.year

  result_page = a.submit(search_form)

  # Find the result
  result = result_page.search('/html/body/form/table[2]/tr/td/table/tr/td/table/tr/td').text.match /Ihre Suchanfrage lieferte (.*?) Treffer/
  result_number = result[1]

  #print "Rücklastschriften von #{from.day}.#{from.month}.#{from.year} bis #{to.day}.#{to.month}.#{to.year}:\n#{result_number}\n"

  # Export the result to CSV and download the file
  download_url = result_page.link_with(:text => '[Exportieren]').uri.to_s
  #puts "Downloading #{download_url}..."
  filename = 'payments.csv'
  a.get('https://amt.expercash.net/' + download_url).save! filename
  
  return filename
end


def get_payment_ids(filename)
  payment_list = Array.new
  counter = 0
  File.open(filename, "r") do |infile|
      while (line = infile.gets)
          counter = counter + 1
          next if counter == 1

          payment_id = line.split(';')[0].to_s.gsub("\"", "")
          payment_list.push(payment_id)
          #puts "#{counter}: #{payment_id}"
      end
  end
  
  return payment_list
end


def get_valid_transactions(filename, backcharges)
  payment_list = Hash.new
  counter = 0
  File.open(filename, "r") do |infile|
      while (line = infile.gets)
          counter = counter + 1
          next if counter == 1

          columns = line.split(';')
          payment_id = columns[0].to_s.gsub("\"", "")
          invoice_id = columns[5].to_s.gsub("\"", "")
                    
          if !backcharges.include?(payment_id)
            payment_list.store(payment_id, invoice_id)
          end
      end
  end
  
  return payment_list
end


def save_to_file(transactions, filename = 'valid_transactions.csv')
  File.open(filename, 'w') { |file| 
    transactions.each { |payment_id, transaction_id|
      file.write("#{payment_id};#{transaction_id}\n") 
    }
  }
  
  return filename
end


username = ARGV[0]
password = ARGV[1]
selected_date_day = ARGV[2]
selected_date_month = ARGV[3]
selected_date_year = ARGV[4]

selected_date_day_2 = ARGV[5]
selected_date_month_2 = ARGV[6]
selected_date_year_2 = ARGV[7]

if selected_date_day.nil? 
  current_date = Date.today - 1
  selected_date_day = current_date.day
  selected_date_month = current_date.month
  selected_date_year = current_date.year
end

if selected_date_day_2.nil?
  selected_date_day_2 = selected_date_day
  selected_date_month_2 = selected_date_month
  selected_date_year_2 = selected_date_year_2
end

from = Date.new(selected_date_year.to_i, selected_date_month.to_i, selected_date_day.to_i)
to   = Date.new(selected_date_year_2.to_i, selected_date_month_2.to_i, selected_date_day_2.to_i)
today = Date.today

backcharges = download_backcharges(username, password, from, today)
backcharges_list = get_payment_ids(backcharges)
puts "#{backcharges_list.length} backcharges."

payments = download_payments(username, password, from, today)
payments_list = get_payment_ids(payments)
puts "#{payments_list.length} payments."

backcharges_without_payers = backcharges_list - payments_list
puts "#{backcharges_without_payers.length} backcharges without payments."

transactions = download_transactions(username, password, from, to)
valid_transactions = get_valid_transactions(transactions, backcharges_without_payers)
filename = save_to_file(valid_transactions)
puts "#{valid_transactions.length} valid transactions. Saved to #{filename}."

