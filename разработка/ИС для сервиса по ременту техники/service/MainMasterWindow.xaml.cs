using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace service
{
    /// <summary>
    /// Логика взаимодействия для MainMasterWindow.xaml
    /// </summary>
    public partial class MainMasterWindow : Window
    {
        public serviceEntities3 _context;
        public MainMasterWindow()
        {
            InitializeComponent();
            _context = serviceEntities3.GetContext();
            ЗаявкиDg.ItemsSource = _context.Заявки.Where(el => el.id_мастера == User.master.ID).ToList();

        }

        private void Home(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }
    }
}
